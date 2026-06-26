<?php

namespace App\Services\Orchestration;

use App\Models\EventStore;
use App\Services\CacheService;
use App\Services\EventBusService;
use App\Services\ObservabilityService;
use Illuminate\Support\Facades\Log;

class WorkflowEngineService
{
    protected array $workflows = [];

    public function __construct(
        protected EventBusService $eventBus,
        protected CacheService $cacheService,
        protected ObservabilityService $observability
    ) {
        $this->registerWorkflows();
    }

    protected function registerWorkflows(): void
    {
        $this->workflows = [
            'post.published' => [
                'steps' => [
                    ['service' => 'ai', 'action' => 'optimize_seo', 'async' => true],
                    ['service' => 'search', 'action' => 'index_post', 'async' => true],
                    ['service' => 'cache', 'action' => 'invalidate_homepage', 'async' => false],
                    ['service' => 'analytics', 'action' => 'track_publish', 'async' => true],
                    ['service' => 'notification', 'action' => 'notify_subscribers', 'async' => true],
                ],
                'on_failure' => 'rollback_publish',
            ],
            'media.uploaded' => [
                'steps' => [
                    ['service' => 'media', 'action' => 'optimize_image', 'async' => true],
                    ['service' => 'ai', 'action' => 'generate_metadata', 'async' => true],
                    ['service' => 'cache', 'action' => 'update_media_cache', 'async' => false],
                    ['service' => 'billing', 'action' => 'record_storage', 'async' => true],
                ],
                'on_failure' => 'cleanup_media',
            ],
            'ai.request.completed' => [
                'steps' => [
                    ['service' => 'billing', 'action' => 'record_ai_usage', 'async' => true],
                    ['service' => 'cache', 'action' => 'cache_ai_result', 'async' => false],
                    ['service' => 'observability', 'action' => 'log_ai_event', 'async' => true],
                ],
                'on_failure' => 'log_ai_failure',
            ],
        ];
    }

    public function execute(string $workflowName, array $payload = []): array
    {
        if (!isset($this->workflows[$workflowName])) {
            Log::warning('Unknown workflow', ['workflow' => $workflowName]);
            return ['status' => 'unknown_workflow'];
        }

        $workflow = $this->workflows[$workflowName];
        $results = [];
        $failed = false;

        foreach ($workflow['steps'] as $step) {
            if ($failed) break;

            try {
                $result = $this->executeStep($step, $payload);
                $results[] = [
                    'service' => $step['service'],
                    'action' => $step['action'],
                    'status' => $result['status'] ?? 'completed',
                ];

                if (($result['status'] ?? 'completed') === 'failed') {
                    $failed = true;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'service' => $step['service'],
                    'action' => $step['action'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                $failed = true;
            }
        }

        if ($failed && isset($workflow['on_failure'])) {
            $this->executeFailureHandler($workflow['on_failure'], $payload, $results);
        }

        $this->observability->info('orchestration', "Workflow '{$workflowName}' executed", [
            'steps' => count($results),
            'failed' => $failed,
        ]);

        return [
            'workflow' => $workflowName,
            'status' => $failed ? 'failed' : 'completed',
            'steps' => $results,
        ];
    }

    protected function executeStep(array $step, array $payload): array
    {
        if ($step['async']) {
            $this->eventBus->dispatchAsync(
                "workflow.{$step['service']}.{$step['action']}",
                $payload
            );
            return ['status' => 'dispatched'];
        }

        $result = match ($step['action']) {
            'invalidate_homepage' => $this->cacheService->forget('homepage'),
            'update_media_cache' => $this->cacheService->forgetByPattern('media:*'),
            'cache_ai_result' => $this->cacheService->put(
                'ai:latest:' . md5(json_encode($payload)),
                $payload,
                3600
            ),
            default => ['status' => 'not_implemented'],
        };

        return ['status' => 'completed', 'result' => $result];
    }

    protected function executeFailureHandler(string $handler, array $payload, array $results): void
    {
        Log::warning("Workflow failure handler triggered: {$handler}", [
            'payload' => $payload,
            'results' => $results,
        ]);

        $this->observability->warning('orchestration', "Failure handler: {$handler}", [
            'payload' => $payload,
        ]);
    }

    public function getWorkflowDefinitions(): array
    {
        return array_keys($this->workflows);
    }

    public function getWorkflowSteps(string $name): ?array
    {
        return $this->workflows[$name] ?? null;
    }
}
