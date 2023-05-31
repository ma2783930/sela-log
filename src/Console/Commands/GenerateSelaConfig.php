<?php

namespace Sela\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use Sela\Attributes\SelaProcess;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class GenerateSelaConfig extends Command
{
    protected string $rootNamespace;
    protected string $basePath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sela:generate-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate config of sela log.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $directories = config('sela.directories');
        $files     = (new Finder())->files()
                                   ->name('*.php')
                                   ->in($directories)
                                   ->sortByName();

        $config = [
            'application' => [
                'name'    => config('app.name'),
                'version' => config('app.version')
            ],
            'processes'   => []
        ];

        collect($files)->each(function (SplFileInfo $file) use (&$config) {
            $className = $this->fullQualifiedClassNameFromFile($file);

            if (class_exists($className)) {
                $class = new ReflectionClass($className);
                foreach ($class->getMethods() as $method) {
                    $processNameAttribute = $method->getAttributes(SelaProcess::class);
                    if (!empty($processNameAttribute)) {
                        /** @var $class SelaProcess */
                        $class                                     = $processNameAttribute[0]->newInstance();
                        $config['processes'][$class->process_name] = [
                            'name'      => $class->process_name,
                            'info'      => $class->info,
                            'data_tags' => collect($class->data_tags)
                                ->filter(fn($tag) => !isset($tag['data_tags']))
                                ->values()
                                ->map(function ($tag) {
                                    $data = [
                                        'RType' => 'L',
                                        'name'  => $tag['name'],
                                        'info'  => $tag['info']
                                    ];
                                    if (isset($tag['log_mime']) && $tag['log_mime']) {
                                        $data['log_mime'] = true;
                                    }

                                    return $data;
                                })
                                ->toArray()
                        ];

                        collect($class->data_tags)
                            ->filter(fn($tag) => isset($tag['data_tags']))
                            ->values()
                            ->each(function ($tag) use (&$config) {
                                $config['processes'][$tag['process_name']] = [
                                    'name'      => $tag['process_name'],
                                    'info'      => $tag['info'],
                                    'data_tags' => collect($tag['data_tags'])->map(function ($tag) {
                                        $data = [
                                            'RType' => 'L',
                                            'name'  => $tag['name'],
                                            'info'  => $tag['info']
                                        ];

                                        if (isset($tag['log_mime']) && $tag['log_mime']) {
                                            $data['log_mime'] = true;
                                        }

                                        return $data;
                                    })
                                ];
                            });
                    }
                }
            }
        });

        $fileName = 'logconfig.json';

        if (Storage::disk('sela')->exists($fileName)) {
            Storage::disk('sela')->delete($fileName);
        }

        Storage::disk('sela')->put($fileName, json_encode($config, JSON_PRETTY_PRINT));

        $this->line('=======================================================================================');
        $this->info('Sela configuration files successfully created in:');
        $this->line(Storage::disk('sela')->path($fileName));
        $this->line('=======================================================================================');

        return 0;
    }

    protected function fullQualifiedClassNameFromFile(SplFileInfo $file): string
    {
        $class = trim(Str::replaceFirst(app()->path(), '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        $class = str_replace(
            [DIRECTORY_SEPARATOR, 'App\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        );

        return app()->getNamespace() . $class;
    }
}
