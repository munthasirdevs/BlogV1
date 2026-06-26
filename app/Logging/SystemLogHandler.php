<?php

namespace App\Logging;

use App\Models\SystemLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class SystemLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            SystemLog::create([
                'level' => $record->level->name,
                'channel' => $record->channel,
                'message' => $record->message,
                'context' => $record->context,
                'created_at' => $record->datetime,
            ]);
        } catch (\Exception $e) {
        }
    }
}
