<?php

namespace Sela\Middleware;

use Closure;
use DB;
use Exception;
use Illuminate\Http\Request;
use Sela\Jobs\UpdateSelaLogFiles;
use Sela\Traits\AttributeReader;
use Sela\Traits\HasDatabaseLog;

class SelaLogHandler
{
    use HasDatabaseLog;
    use AttributeReader;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request                                                                          $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     * @throws \ReflectionException
     */
    public function handle(Request $request, Closure $next)
    {
        if (!empty($attributeClass = $this->getProcessAttribute($request))) {
            try {
                if ($attributeClass->auto_log) {

                    DB::transaction(function () use ($request, $attributeClass) {

                        $action = $this->insertActionLog($attributeClass->process_name);

                        collect($attributeClass->data_tags)
                            ->filter(fn($data_tag) => !isset($data_tag['data_tags']))
                            ->each(function ($data_tag) use ($action, $request) {

                                if ($request->has($data_tag['name'])) {

                                    $this->insertDetailLog(
                                        $action,
                                        $data_tag['name'],
                                        $request->{$data_tag['name']},
                                        $data_tag['log_mime'] ?? false
                                    );

                                } else {

                                    $foreignKeyName = str($data_tag['name'])->rtrim('_id')->camel()->toString();
                                    $value          = $request->route()->originalParameter($foreignKeyName) ??
                                        $request->route()->originalParameter($data_tag['name']);

                                    $this->insertDetailLog(
                                        $action,
                                        $data_tag['name'],
                                        $value
                                    );

                                }

                            });

                        collect($attributeClass->data_tags)
                            ->filter(fn($data_tag) => isset($data_tag['data_tags']))
                            ->each(function ($childProcess) use ($attributeClass, $request) {

                                if ($childProcess['is_multi']) {

                                    if ($childProcess['base64_array']) {
                                        foreach ($request->input($childProcess['name'], []) as $value) {
                                            $childAction = $this->insertActionLog($childProcess['process_name'], $attributeClass->process_name);
                                            $this->insertDetailLog(
                                                $childAction,
                                                'file_content',
                                                $value ?? "",
                                                true
                                            );
                                        }
                                    } else {
                                        foreach ($request->input($childProcess['name'], []) as $values) {
                                            $childAction = $this->insertActionLog($childProcess['process_name'], $attributeClass->process_name);
                                            foreach ($childProcess['data_tags'] as $childTag) {
                                                $this->insertDetailLog(
                                                    $childAction,
                                                    $childTag['name'],
                                                    $values[$childTag['name']] ?? "",
                                                    $childTag['log_mime'] ?? false
                                                );
                                            }
                                        }
                                    }

                                } else {

                                    $values = $request->input($childProcess['name']);
                                    $childAction = $this->insertActionLog($childProcess['process_name'], $attributeClass->process_name);
                                    foreach ($childProcess['data_tags'] as $childTag) {
                                        $this->insertDetailLog(
                                            $childAction,
                                            $childTag['name'],
                                            $values[$childTag['name']] ?? "",
                                            $childTag['log_mime'] ?? false
                                        );
                                    }
                                }

                            });

                        UpdateSelaLogFiles::dispatch();

                    });
                }
            } catch (Exception $exception) {
                //
            }
        }
        return $next($request);
    }
}
