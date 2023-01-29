<?php

namespace Sela\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Sela\Models\SelaActionLog;

class UpdateSelaActionLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pendingLogs = SelaActionLog::all()
                                    ->map(fn($log) => json_encode([
                                        'id'          => $log->id,
                                        'parent_proc' => $log->parent_proc,
                                        'timestamp'   => $log->timestamp,
                                        'process_tag' => $log->process_tag,
                                        'user_name'   => $log->user_name
                                    ]))
                                    ->map(fn($log) => str_replace(',', ', ', $log))
                                    ->toArray();

        $filePath = Storage::path('logs/sela/actionlog.json');
        if (!file_exists($filePath)) {
            $fileStream = fopen($filePath, 'w');
            fclose($fileStream);
        }

        $fileStream = fopen($filePath, 'a');
        fwrite($fileStream, implode("\n", $pendingLogs));
        fwrite($fileStream, "\n");
        fclose($fileStream);


    }
}
