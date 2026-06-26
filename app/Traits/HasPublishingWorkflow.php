<?php

namespace App\Traits;

use App\Jobs\PublishScheduledPostJob;
use App\Models\ScheduledJob;
use Carbon\Carbon;

trait HasPublishingWorkflow
{
    public function publish(): bool
    {
        return $this->update([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
            'is_scheduled' => false,
        ]);
    }

    public function schedule(Carbon $dateTime, ?string $timezone = null): bool
    {
        $data = [
            'status' => 'scheduled',
            'scheduled_at' => $dateTime,
            'is_scheduled' => true,
        ];

        if ($timezone) {
            $data['publish_timezone'] = $timezone;
        }

        $result = $this->update($data);

        if ($result) {
            $scheduledJob = ScheduledJob::create([
                'post_id' => $this->id,
                'job_type' => 'publish',
                'scheduled_at' => $dateTime,
                'status' => 'pending',
            ]);

            PublishScheduledPostJob::dispatch($this->id, $scheduledJob->id)->delay($dateTime);
        }

        return $result;
    }

    public function archive(): bool
    {
        return $this->update([
            'status' => 'archived',
        ]);
    }

    public function unschedule(): bool
    {
        ScheduledJob::where('post_id', $this->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return $this->update([
            'status' => 'draft',
            'is_scheduled' => false,
            'scheduled_at' => null,
        ]);
    }
}
