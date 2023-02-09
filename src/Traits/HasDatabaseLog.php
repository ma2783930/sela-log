<?php

namespace Sela\Traits;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Sela\Models\ActionLog;
use Storage;
use Str;

trait HasDatabaseLog
{
    /**
     * @param string      $processName
     * @param string|null $parentProc
     * @return \Sela\Models\ActionLog
     * @noinspection PhpUndefinedFieldInspection
     */
    public function insertActionLog(string $processName, string $parentProc = null): ActionLog
    {
        return ActionLog::forceCreate([
            'parent_proc' => $parentProc,
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

            $dateFormat            = verta()->format(config('sela.path_date_format', 'Y_m_d'));
            $directoryPath         = $dateFormat . '/files';
            $relativeDirectoryPath = './files';

            try {

                if ($value instanceof UploadedFile) {

                    $fileName = Str::uuid() . '.' . $value->guessExtension();
                    $mimeType = $value->getMimeType();
                    Storage::disk('sela')->put("{$directoryPath}/{$fileName}", $value->getContent());

                } else {

                    $file     = base64_to_file($value);
                    $fileName = sprintf('%s.%s', time(), $file->extension());
                    $mimeType = $file->getMimeType();
                    Storage::disk('sela')->put("{$directoryPath}/{$fileName}", $file->getContent());

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
                Storage::disk('sela')->put("{$directoryPath}/{$fileName}}", $value);

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
