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

                        foreach ($attributeClass->data_tags as $data_tag) {
                            if ($request->has($data_tag['name'])) {
                                $this->insertDetailLog($action, $data_tag['name'], $request->{$data_tag['name']}, $data_tag['log_mime'] ?? false);
                            } else if (!empty($value = $request->route()->originalParameter(str($data_tag['name'])->rtrim('_id')->camel()->toString()))) {
                                $this->insertDetailLog($action, $data_tag['name'], $value);
                            }
                        }

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
