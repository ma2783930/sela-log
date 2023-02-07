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
use Sela\Traits\PathHelper;

class UpdateSelaLogFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use PathHelper;

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
                            $this->touchFile($filePath);
                            $this->appendActionLogsToFile($actions, $filePath);
                            $actions->each(function (ActionLog $action) {
                                $action->details()
                                       ->orderBy('log_mime')
                                       ->get()
                                       ->groupBy('file_name')
                                       ->each(function (Collection $details, $filePath) {
                                           $this->touchFile($filePath);
                                           $this->appendDetailLogsToFile($details, $filePath);
                                       });
                                $action->mimes()
                                       ->get()
                                       ->groupBy('file_name')
                                       ->each(function (Collection $mimes, $filePath) {
                                           $this->touchFile($filePath);
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
     * @param string $filePath
     * @return void
     */
    private function touchFile(string $filePath): void
    {
        $path = $this->getFullPath($filePath);
        if (!file_exists($path)) {
            $fileStream = fopen($path, 'w');
            fclose($fileStream);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $actions
     * @param string                                   $filePath
     * @return void
     */
    private function appendActionLogsToFile(Collection $actions, string $filePath): void
    {
        $fileStream = fopen($this->getFullPath($filePath), 'a');
        fwrite($fileStream, implode(
                "\n",
                $actions
                    ->map(fn(ActionLog $log) => json_encode([
                        'parent_proc' => $log->parent_proc,
                        'process_tag' => $log->process_tag,
                        'id'          => $log->id,
                        'user_name'   => $log->user_name,
                        'timestamp'   => $log->timestamp,
                    ]))
                    ->map(fn($log) => str_replace(',', ', ', $log))
                    ->toArray())
        );
        fwrite($fileStream, "\n");
        fclose($fileStream);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $details
     * @param string                                   $filePath
     * @return void
     */
    private function appendDetailLogsToFile(Collection $details, string $filePath): void
    {
        $fileStream = fopen($this->getFullPath($filePath), 'a');
        fwrite($fileStream, implode(
                "\n",
                $details
                    ->map(fn(DetailLog $log) => json_encode([
                        'actionlog_id' => $log->actionlog_id,
                        'data_tag'     => $log->data_tag,
                        'value'        => $log->value
                    ]))
                    ->map(fn($log) => str_replace(',', ', ', $log))
                    ->toArray())
        );
        fwrite($fileStream, "\n");
        fclose($fileStream);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $mimes
     * @param string                                   $filePath
     * @return void
     */
    private function appendMimeLogsToFile(Collection $mimes, string $filePath): void
    {
        $fileStream = fopen($this->getFullPath($filePath), 'a');
        fwrite($fileStream, implode(
                "\n",
                $mimes
                    ->map(fn(MimeLog $log) => json_encode([
                        'actionlog_id' => $log->actionlog_id,
                        'data_tag'     => $log->data_tag,
                        'value'        => $log->value,
                        'mime'         => $log->mime
                    ]))
                    ->map(fn($log) => str_replace(',', ', ', $log))
                    ->toArray())
        );
        fwrite($fileStream, "\n");
        fclose($fileStream);
    }
}
