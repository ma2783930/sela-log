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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!empty($route = $request->route())) {

                if (
                    !empty($route->getName()) &&
                    !empty($config = $this->getRouteSelaConfig($route->getName()))
                ) {
                    /** @var $actionLog SelaActionLog */
                    $actionLog = SelaActionLog::forceCreate([
                        'parent_proc' => null,
                        'process_tag' => $config['process_tag'],
                        'id'          => Str::uuid(),
                        'user_name'   => Auth::user() ? Auth::user()->username : 'guest',
                        'timestamp'   => Carbon::now()->toISOString(true)
                    ]);

                    collect($config['data_tags'])
                        ->filter(fn($dataTag) => !isset($dataTag['log_mime']))
                        ->each(function ($dataTag) use ($request, $actionLog) {
                            $this->saveDetailLog($actionLog, $dataTag['name'], $request);
                        });

                    collect($config['data_tags'])
                        ->filter(fn($dataTag) => isset($dataTag['log_mime']) && $dataTag['log_mime'])
                        ->each(function ($dataTag) use ($request, $actionLog) {
                            $this->saveDetailLog($actionLog, $dataTag['name'], $request, true);
                        });

                }
            }
        } catch (Exception $exception) {
            //
        }
        return $next($request);
    }

    /**
     * @param                          $tagName
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\UploadedFile|string|null
     */
    public function getDataTagValue($tagName, Request $request): string|UploadedFile|null
    {
        if (!empty($value = $request->input($tagName))) {
            return (string)$value;
        }

        foreach ($request->route()->parameters as $paramName => $paramValue) {
            if ($tagName == "{$paramName}_id") {
                if (is_string($paramValue)) {
                    return $paramValue;
                }

                if ($paramValue instanceof Model) {
                    return (string)$paramValue->getKey();
                }

                if ($paramValue instanceof UploadedFile) {
                    return $paramValue;
                }
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
        if ($logMime) {

            $actionLog->selaDetailLogs()->create([
                'id'       => Str::uuid(),
                'data_tag' => $tagName,
                'value'    => $tagValue
            ]);

        } else {

            $date          = verta();
            $filePath      = sprintf('/log/sela/files/%s/%s/%s', $date->year, $date->month, $date->day);
            $filePathInLog = sprintf('./files/%s/%s/%s', $date->year, $date->month, $date->day);

            try {

                if ($tagValue instanceof UploadedFile) {

                    if (!file_exists($filePath)) {
                        mkdir($filePath, '0775', true);
                    }

                    $fileName = sprintf('%s_%s', time(), $tagValue->getClientOriginalName());
                    FileFacade::copy($tagValue->path(), sprintf('%s/%s', $filePath, $fileName));

                } else {

                    $file     = $this->convertBase64ToFile($tagValue);
                    $fileName = sprintf('%s.%s', time(), $file->extension());
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
                    'value'    => $logValue
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
     * @noinspection PhpUndefinedFunctionInspection
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
