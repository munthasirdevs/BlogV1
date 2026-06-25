<?php

namespace App\Traits;

use App\Jobs\PublishScheduledPostJob;
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

    public function schedule(Carbon $dateTime): bool
    {
        $result = $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $dateTime,
            'is_scheduled' => true,
        ]);

        if ($result) {
            PublishScheduledPostJob::dispatch($this->id)->delay($dateTime);
        }

        return $result;
    }

    public function archive(): bool
    {
        return $this->update([
            'status' => 'archived',
        ]);
    }
}
