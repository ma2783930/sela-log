<?php

namespace Sela\Middleware;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File as FileFacade;
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
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        try {
            if (!empty($route = $request->route())) {
                if (
                    !empty($route->getName()) &&
                    !empty($config = $this->getRouteSelaConfig($route->getName()))
                ) {

                    $logged = [];
                    $action = ActionLog::forceCreate([
                        'process_tag' => $config['process_tag'],
                        'user_name'   => Auth::user() ? Auth::user()->username : 'guest',
                    ]);

                    $dataTags = collect($config['data_tags']);
                    $data     = $request->all();

                    /*
                    |--------------------------------------------------------------------------
                    | Log inputs from request body
                    |--------------------------------------------------------------------------
                    */

                    foreach ($data as $name => $value) {
                        $tag = $dataTags->where('name', $name)->first();
                        if (!empty($tag) && !in_array($tag['name'], $logged)) {
                            $this->saveDetailLog(
                                $action,
                                $tag['name'],
                                $value,
                                isset($tag['log_mime']) && !empty($tag['log_mime'])
                            );
                            $logged[] = $tag['name'];
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Log inputs from request parameters (Route model binding)
                    |--------------------------------------------------------------------------
                    */

                    foreach ($route->parameters as $paramName => $modelInstance) {
                        $tag = $dataTags->whereIn('name', ["{$paramName}_id", "id"])->first();
                        if (!empty($tag) && !in_array($tag['name'], $logged)) {
                            $this->saveDetailLog(
                                $action,
                                $tag['name'],
                                $modelInstance instanceof Model ? $modelInstance->getKey() : $modelInstance,
                                isset($tag['log_mime'])
                            );
                            $logged[] = $tag['name'];
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Dispatch log updater job
                    |--------------------------------------------------------------------------
                    */

                    UpdateSelaLogFiles::dispatch();

                }
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
