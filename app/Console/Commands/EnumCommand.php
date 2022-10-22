<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class EnumCommand extends GeneratorCommand
{
    /**
     * Constant variable to indicate the given type for enum is integer.
     */
    private const ENUM_TYPE_INT = 'int';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:enum
                            {name}
                            {--c|cases=* : Generate a enum cases.}
                            {--t|type= : The type of enum. It can be integer or string.}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make enum command';

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $this->replaceNamespace($stub, $name);

        $this->replaceEnumName($stub, $name);

        $this->replaceEnumType($stub);

        $this->replaceCases($stub);

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath("/stubs/enum.stub");
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Replace the name for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return void
     */
    protected function replaceEnumName(string &$stub, string $name): void
    {
        $stub = str_replace('{{ enum }}', last(explode('\\', $name)), $stub);
    }

    /**
     * Replace the enum type for the given stub.
     *
     * @param string $stub
     * @return void
     */
    protected function replaceEnumType(string &$stub): void
    {
        $replacer = '';

        if ($this->option('type') && is_string($this->option('type'))) {
            $replacer = ':' . $this->option('type');
        }

        $stub = str_replace('{{ type }}', $replacer, $stub);
    }

    /**
     * Replace the cases for the given stub.
     *
     * @param string $stub
     * @return void
     */
    protected function replaceCases(string &$stub): void
    {
        if (!$this->option('cases') | empty($this->option('cases'))) {
            $stub = str_replace('{{ cases }}', '', $stub);

            return;
        }

        $replacer = [];

        foreach ((array)$this->option('cases') as $case) {
            $replacer[] = $this->option('type') === self::ENUM_TYPE_INT
                ? "case ${case};"
                : "case ${case} = " . "'" . implode('.', array_map(fn ($str) => Str::lower($str), Str::ucsplit($case))) . "';";
        }

        $stub = str_replace('{{ cases }}', implode("\n\t", $replacer), $stub);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return is_dir(app_path('Enums')) ? $rootNamespace . '\\Enums' : $rootNamespace;
    }
}
