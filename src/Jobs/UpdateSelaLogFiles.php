<?php

namespace Sela\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Sela\Models\ActionLog;
use Sela\Models\DetailLog;
use Sela\Models\MimeLog;
use Storage;

class UpdateSelaLogFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $encodeFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

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
    public function handle(): void
    {
        DB::transaction(function () {
            $actions = tap(ActionLog::oldest('timestamp')->lockForUpdate()->get(), function (Collection $actions) {
                $actions->groupBy('file_name')
                        ->each(function (Collection $actions, $filePath) {
                            $this->appendActionLogsToFile($actions, $filePath);
                            $actions->each(function (ActionLog $action) {
                                $action->details()
                                       ->orderBy('log_mime')
                                       ->get()
                                       ->groupBy('file_name')
                                       ->each(function (Collection $details, $filePath) {
                                           $this->appendDetailLogsToFile($details, $filePath);
                                       });
                                $action->mimes()
                                       ->get()
                                       ->groupBy('file_name')
                                       ->each(function (Collection $mimes, $filePath) {
                                           $this->appendMimeLogsToFile($mimes, $filePath);
                                       });
                            });
                        });
            });

            $actions->each(function (ActionLog $action) {
                $action->details()->delete();
                $action->mimes()->delete();
                $action->delete();
            });
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $actions
     * @param string                                   $filePath
     * @return void
     */
    private function appendActionLogsToFile(Collection $actions, string $filePath): void
    {
        Storage::disk('sela')->append(
            $filePath,
            implode(
                "\n",
                $actions
                    ->map(fn(ActionLog $log) => json_encode([
                        'parent_proc' => $log->parent_proc,
                        'process_tag' => $log->process_tag,
                        'id'          => $log->id,
                        'user_name'   => $log->user_name,
                        'timestamp'   => $log->timestamp,
                    ], $this->encodeFlags))
                    ->map(fn($log) => str_replace(',', ', ', $log))
                    ->toArray())
        );
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $details
     * @param string                                   $filePath
     * @return void
     */
    private function appendDetailLogsToFile(Collection $details, string $filePath): void
    {
        Storage::disk('sela')->append(
            $filePath,
            implode(
                "\n",
                $details
                    ->map(fn(DetailLog $log) => json_encode([
                        'actionlog_id' => $log->actionlog_id,
                        'data_tag'     => $log->data_tag,
                        'value'        => $log->value
                    ], $this->encodeFlags))
                    ->map(fn($log) => str_replace(',', ', ', $log))
                    ->toArray())
        );
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $mimes
     * @param string                                   $filePath
     * @return void
     */
    private function appendMimeLogsToFile(Collection $mimes, string $filePath): void
    {
        Storage::disk('sela')->append(
            $filePath,
            implode(
                "\n",
                $mimes
                    ->map(fn(MimeLog $log) => json_encode([
                        'actionlog_id' => $log->actionlog_id,
                        'data_tag'     => $log->data_tag,
                        'value'        => $log->value,
                        'mime'         => $log->mime
                    ], $this->encodeFlags))
                    ->map(fn($log) => str_replace(',', ', ', $log))
                    ->toArray())
        );
    }
}
