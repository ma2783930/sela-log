<?php

namespace Sela\Console\Commands;

use Sela\Attributes\SelaProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;
use Sela\Traits\PathHelper;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class GenerateSelaConfig extends Command
{
    use PathHelper;

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
        $directory = app_path('Http/Controllers');
        $files     = (new Finder())->files()
                                   ->name('*.php')
                                   ->in($directory)
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
                    $process_nameAttribute = $method->getAttributes(SelaProcess::class);
                    if (!empty($process_nameAttribute)) {
                        /** @var $class SelaProcess */
                        $class                                    = $process_nameAttribute[0]->newInstance();
                        $config['processes'][$class->process_name] = [
                            'name'     => $class->process_name,
                            'info'     => $class->info,
                            'data_tags' => $class->data_tags
                        ];
                    }
                }
            }
        });

        $path = $this->getFullPath('logconfig.json');
        if (file_exists($path)) {
            unlink($path);
        }

        $fileStream = fopen($path, 'w');
        fwrite($fileStream, json_encode($config, JSON_PRETTY_PRINT));
        fclose($fileStream);

        $this->line('=======================================================================================');
        $this->info('Sela configuration files successfully created in:');
        $this->line($path);
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
