<?php

namespace Sela\Traits;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File as FileFacade;
use Sela\Models\ActionLog;
use Str;

trait HasDatabaseLog
{
    /**
     * @param string $processName
     * @return \Sela\Models\ActionLog
     * @noinspection PhpUndefinedFieldInspection
     */
    public function insertActionLog(string $processName): ActionLog
    {
        return ActionLog::forceCreate([
            'process_tag' => $processName,
            'user_name'   => Auth::user() ? Auth::user()->username : 'guest',
        ]);
    }

    /**
     * @param \Sela\Models\ActionLog   $action
     * @param                          $tag
     * @param                          $value
     * @param bool                     $logMime
     * @return void
     */
    public function insertDetailLog(ActionLog $action, $tag, $value, bool $logMime = false): void
    {
        if (!$logMime) {

            /*
            |--------------------------------------------------------------------------
            | Save detail log as normal data tag
            |--------------------------------------------------------------------------
            */

            $action->details()->create([
                'data_tag' => $tag,
                'value'    => $value ?? ""
            ]);

        } else {

            $dateFormat            = config('sela.path_date_format', 'Y_m_d');
            $directoryPath         = storage_path(config('sela.path') . '/files/' . $dateFormat);
            $relativeDirectoryPath = './files/' . $dateFormat;

            try {

                if ($value instanceof UploadedFile) {

                    if (!file_exists($directoryPath)) {
                        mkdir($directoryPath, '0775', true);
                    }

                    $fileName = Str::uuid() . '.' . $value->guessExtension();
                    $mimeType = $value->getMimeType();
                    FileFacade::copy($value->path(), sprintf('%s/%s', $directoryPath, $fileName));

                } else {

                    $file     = base64_to_file($value);
                    $fileName = sprintf('%s.%s', time(), $file->extension());
                    $mimeType = $file->getMimeType();
                    $file->move($directoryPath, $fileName);

                }

                $logValue = sprintf('%s/%s', $relativeDirectoryPath, $fileName);

                /*
                |--------------------------------------------------------------------------
                | Save detail log as normal data tag
                |--------------------------------------------------------------------------
                */

                $action->details()->create([
                    'data_tag' => $tag,
                    'value'    => $logValue,
                    'log_mime' => true
                ]);

                /*
                |--------------------------------------------------------------------------
                | Save detail log as mime data tag
                |--------------------------------------------------------------------------
                */

                $action->mimes()->create([
                    'data_tag' => $tag,
                    'value'    => $logValue,
                    'mime'     => $mimeType
                ]);

            } catch (Exception $exception) {

                /*
                |--------------------------------------------------------------------------
                | Save invalid file data as normal and mime data tag
                |--------------------------------------------------------------------------
                */

                $fileName = time() . '.' . '.tmp';
                file_put_contents(sprintf('%s/%s', $directoryPath, $fileName), $value);

                $path = sprintf('%s/%s', $relativeDirectoryPath, $fileName);

                $action->details()->create([
                    'data_tag' => $tag,
                    'value'    => $path,
                    'log_mime' => true
                ]);

                $action->mimes()->create([
                    'data_tag' => $tag,
                    'value'    => $path
                ]);

            }

        }
    }
}
