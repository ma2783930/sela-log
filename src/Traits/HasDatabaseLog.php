<?php

namespace Sela\Traits;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Sela\Models\ActionLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;

trait HasDatabaseLog
{
    /**
     * @param string $processName
     * @param string|null $parentProc
     * @return \Sela\Models\ActionLog
     * @noinspection PhpUndefinedFieldInspection
     */
    public function insertActionLog(string $processName, string $parentProc = null): ActionLog
    {
        return ActionLog::forceCreate([
            'parent_proc' => $parentProc,
            'process_tag' => $processName,
            'user_name' => Auth::user() ? Auth::user()->username : 'guest',
        ]);
    }

    /**
     * @param \Sela\Models\ActionLog $action
     * @param $tag
     * @param $value
     * @param bool $logMime
     * @return void
     */
    public function insertDetailLog(ActionLog $action, $tag, $value, bool $logMime = false): void
    {
        if (!$logMime) {
            //save detail log as normal data tag
            $action->details()->create([
                'data_tag' => $tag,
                'value' => $value ?? ''
            ]);
        } else {

            $dateFormat = verta()->format(config('sela.path_date_format', 'Y_m_d'));
            $directoryPath = './files/' . $dateFormat;

            try {

                if ($value instanceof UploadedFile) {
                    $fileName = sprintf('%s.%s', Str::uuid(), $value->guessExtension());
                    $mimeType = $value->getMimeType();
                    Storage::disk('sela')->put("{$directoryPath}/{$fileName}", $value->getContent());
                } else {
                    $file = $this->base64_to_file($value);
                    $fileName = sprintf('%s.%s', Str::uuid(), $file->extension());
                    $mimeType = $file->getMimeType();
                    Storage::disk('sela')->put("{$directoryPath}/{$fileName}", $file->getContent());
                }

                $logValue = sprintf('%s/%s', $directoryPath, $fileName);

                //save detail log as normal data tag
                $action->details()->create([
                    'data_tag' => $tag,
                    'value' => $logValue,
                    'log_mime' => true
                ]);

                //save detail log as mime data tag
                $action->mimes()->create([
                    'data_tag' => $tag,
                    'value' => $logValue,
                    'mime' => $mimeType
                ]);
            } catch (Exception $e) {

                //save invalid file data as normal and mime data tag
                
                $fileName = time() . '.' . '.tmp';
                Storage::disk('sela')->put("{$directoryPath}/{$fileName}}", $value);

                $path = sprintf('%s/%s', $directoryPath, $fileName);

                $action->details()->create([
                    'data_tag' => $tag,
                    'value' => $path,
                    'log_mime' => true
                ]);

                $action->mimes()->create([
                    'data_tag' => $tag,
                    'value' => $path
                ]);
            }
        }
    }

    /**
     * @param string $value
     * @return UploadedFile
     */
    public function base64_to_file(string $value): UploadedFile
    {
        if (str_contains($value, ';base64')) {
            [, $value] = explode(';', $value);
            [, $value] = explode(',', $value);
        }

        $binaryData = base64_decode($value);
        $tmpFile = tmpfile();
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];

        file_put_contents($tmpFilePath, $binaryData);

        $tmpFileObject = new File($tmpFilePath);
        $file = new UploadedFile(
            $tmpFileObject->getPathname(),
            $tmpFileObject->getFilename(),
            $tmpFileObject->getMimeType(),
            0,
            true
        );

        app()->terminating(function () use ($tmpFile) {
            fclose($tmpFile);
        });

        return $file;
    }
}
