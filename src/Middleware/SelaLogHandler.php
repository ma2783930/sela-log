<?php

namespace Sela\Middleware;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Str;
use Sela\Models\SelaActionLog;
use Symfony\Component\HttpFoundation\File\File;

class SelaLogHandler
{
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
            if (!empty($route = $request->route())) {

                $loggedTags = [];

                if (
                    !empty($route->getName()) &&
                    !empty($config = $this->getRouteSelaConfig($route->getName()))
                ) {
                    $actionLog = SelaActionLog::forceCreate([
                        'parent_proc' => null,
                        'process_tag' => $config['process_tag'],
                        'id'          => Str::uuid(),
                        'user_name'   => Auth::user() ? Auth::user()->username : 'guest',
                        'timestamp'   => Carbon::now()->toISOString(true)
                    ]);

                    $dataTags = collect($config['data_tags']);
                    $data     = $request->all();
                    foreach ($data as $inputName => $inputValue) {
                        $tag = $dataTags->where('name', $inputName)->first();
                        if (!empty($tag) && !in_array($tag['name'], $loggedTags) && !empty($inputValue)) {
                            $this->saveDetailLog($actionLog, $tag['name'], $request, isset($tag['log_mime']));
                            $loggedTags[] = $tag['name'];
                        }
                    }
                    foreach ($route->parameters as $paramName => $modelInstance) {
                        $tag = $dataTags->whereIn('name', ["{$paramName}_id", "id"])->first();
                        if (!empty($tag) && !in_array($tag['name'], $loggedTags)) {
                            $this->saveDetailLog($actionLog, $tag['name'], $request, isset($tag['log_mime']));
                            $loggedTags[] = $tag['name'];
                        }
                    }

                }
            }
        } catch (Exception $exception) {
            //
        }
        return $next($request);
    }


    public function getDataTagValue($tagName, Request $request)
    {
        $data = $request->all();
        foreach ($data as $inputName => $inputValue) {
            if ($tagName == $inputName) {
                return $inputValue;
            }
        }
        foreach ($request->route()->parameters as $paramName => $modelInstance) {
            if (in_array($tagName, ["{$paramName}_id", "id"])) {
                return $modelInstance instanceof Model ? $modelInstance->getKey() : $modelInstance;
            }
        }

        return null;
    }

    /**
     * @param $route_name
     * @return array|null
     */
    public function getRouteSelaConfig($route_name): ?array
    {
        foreach (Config::get('sela_log.identifiers') as $section => $configurations) {
            foreach ($configurations as $index => $config) {
                if ($route_name == $config['route']) {
                    return [
                        'process_tag' => "{$section}_{$index}",
                        ...$config
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param \Sela\Models\SelaActionLog $actionLog
     * @param                            $tagName
     * @param \Illuminate\Http\Request   $request
     * @param bool                       $logMime
     * @return void
     */
    public function saveDetailLog(SelaActionLog $actionLog, $tagName, Request $request, bool $logMime = false): void
    {
        $tagValue = $this->getDataTagValue($tagName, $request);
        if (!$logMime) {

            $actionLog->selaDetailLogs()->create([
                'id'       => Str::uuid(),
                'data_tag' => $tagName,
                'value'    => $tagValue
            ]);

        } else {

            $filePath      = storage_path('/logs/sela/files');
            $filePathInLog = './files';

            try {

                if ($tagValue instanceof UploadedFile) {

                    if (!file_exists($filePath)) {
                        mkdir($filePath, '0775', true);
                    }

                    $fileName = sprintf('%s_%s', time(), $tagValue->getClientOriginalName());
                    $mimeType = $tagValue->getMimeType();
                    FileFacade::copy($tagValue->path(), sprintf('%s/%s', $filePath, $fileName));

                } else {

                    $file     = $this->convertBase64ToFile($tagValue);
                    $fileName = sprintf('%s.%s', time(), $file->extension());
                    $mimeType = $file->getMimeType();
                    $file->move($filePath, $fileName);

                }

                $logValue = sprintf('%s/%s', $filePathInLog, $fileName);

                $actionLog->selaDetailLogs()->create([
                    'id'       => Str::uuid(),
                    'data_tag' => $tagName,
                    'value'    => $logValue
                ]);
                $actionLog->selaMimeLogs()->create([
                    'id'       => Str::uuid(),
                    'data_tag' => $tagName,
                    'value'    => $logValue,
                    'mime'     => $mimeType
                ]);

            } catch (Exception $exception) {

                $fileName = time() . '.' . '.tmp';
                file_put_contents(sprintf('%s/%s', $filePath, $fileName), $tagValue);

                $logValue = sprintf('%s/%s', $filePathInLog, $fileName);

                $actionLog->selaDetailLogs()->create([
                    'id'       => Str::uuid(),
                    'data_tag' => $tagName,
                    'value'    => $logValue
                ]);
                $actionLog->selaMimeLogs()->create([
                    'id'       => Str::uuid(),
                    'data_tag' => $tagName,
                    'value'    => $logValue
                ]);

            }

        }
    }

    /**
     * @param string $value
     * @return \Illuminate\Http\UploadedFile
     */
    public function convertBase64ToFile(string $value): UploadedFile
    {
        if (str_contains($value, ';base64')) {
            [, $value] = explode(';', $value);
            [, $value] = explode(',', $value);
        }

        $binaryData  = base64_decode($value);
        $tmpFile     = tmpfile();
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];

        file_put_contents($tmpFilePath, $binaryData);

        $tmpFileObject = new File($tmpFilePath);
        $file          = new UploadedFile(
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
