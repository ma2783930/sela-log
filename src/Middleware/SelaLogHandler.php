<?php

namespace Sela\Middleware;

use Closure;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File as FileFacade;
use ReflectionClass;
use Sela\Attributes\SelaProcess;
use Sela\Jobs\UpdateSelaLogFiles;
use Sela\Models\ActionLog;
use Sela\Traits\Base64Helper;
use Sela\Traits\SelaRouteHelper;

class SelaLogHandler
{
    use Base64Helper;
    use SelaRouteHelper;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request                                                                          $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     * @noinspection PhpUndefinedFieldInspection
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $method     = $request->route()->getActionMethod();
            $controller = get_class($request->route()->controller);
            $reflection = new ReflectionClass($controller);
            $method     = $reflection->getMethod($method);
            $attribute  = $method->getAttributes(SelaProcess::class);
            if (!empty($attribute)) {
                /** @var $attributeClass SelaProcess */
                $attributeClass = $attribute[0]->newInstance();

                DB::transaction(function () use ($request, $attributeClass) {

                    $action = ActionLog::forceCreate([
                        'process_tag' => $attributeClass->process_name,
                        'user_name'   => Auth::user() ? Auth::user()->username : 'guest',
                    ]);

                    foreach ($attributeClass->data_tags as $data_tag) {
                        if ($request->has($data_tag['name'])) {
                            $this->saveDetailLog($action, $data_tag['name'], $request->{$data_tag}, $data_tag['log_mime'] ?? false);
                        } else if (!empty($value = $request->route()->originalParameter("{$data_tag['name']}_id"))) {
                            $this->saveDetailLog($action, $data_tag['name'], $value, $data_tag['log_mime'] ?? false);
                        }
                    }

                    UpdateSelaLogFiles::dispatch();

                });

            }
        } catch (Exception $exception) {
            //
        }

        return $next($request);
    }

    /**
     * @param \Sela\Models\ActionLog   $action
     * @param                          $tag
     * @param                          $value
     * @param bool                     $logMime
     * @return void
     */
    public function saveDetailLog(ActionLog $action, $tag, $value, bool $logMime = false): void
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

            $filePath      = storage_path('/logs/sela/files');
            $filePathInLog = './files';

            try {

                if ($value instanceof UploadedFile) {

                    if (!file_exists($filePath)) {
                        mkdir($filePath, '0775', true);
                    }

                    $fileName = sprintf('%s_%s', time(), $value->getClientOriginalName());
                    $mimeType = $value->getMimeType();
                    FileFacade::copy($value->path(), sprintf('%s/%s', $filePath, $fileName));

                } else {

                    $file     = $this->convertBase64ToFile($value);
                    $fileName = sprintf('%s.%s', time(), $file->extension());
                    $mimeType = $file->getMimeType();
                    $file->move($filePath, $fileName);

                }

                $logValue = sprintf('%s/%s', $filePathInLog, $fileName);

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
                file_put_contents(sprintf('%s/%s', $filePath, $fileName), $value);

                $path = sprintf('%s/%s', $filePathInLog, $fileName);

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
