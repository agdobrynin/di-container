<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompiledEntries;
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Compiler\ContainerCompilerToFile;
use Kaspi\DiContainer\Compiler\DiContainerDefinitions;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\IdsIterator;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\ContainerBuilderException;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\DiContainerBuilderInterface;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

use function class_exists;
use function file_exists;
use function sprintf;

final class DiContainerBuilder implements DiContainerBuilderInterface
{
    /**
     * @var list<array{
     *  override: bool,
     *  file: non-empty-string
     * }>
     */
    private array $loadFiles = [];

    /**
     * @var list<array{
     *  namespace: non-empty-string,
     *  src: non-empty-string,
     *  exclude_files: list<non-empty-string>,
     *  available_extensions: list<non-empty-string>,
     * }>
     */
    private array $imports = [];

    /**
     * @var list<array{
     *  override: bool,
     *  definitions: iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed>
     * }>
     */
    private array $definitions = [];

    /**
     * @var non-empty-string
     */
    private string $compilerOutputDirectory;

    /**
     * @var class-string
     */
    private string $compilerContainerClass;
    private int $compilerPermissionCompiledContainerFile;
    private bool $compilerIsExclusiveLockFile;
    private DiDefinitionTransformerInterface $compilerDiDefinitionTransformer;
    private CompiledEntriesInterface $compiledEntries;

    /**
     * @var array{
     *  invalid_behavior?: InvalidBehaviorCompileEnum,
     *  di_definition_transformer?: DiDefinitionTransformerInterface,
     *  compiled_entries?: CompiledEntriesInterface,
     *  force_rebuild?: bool,
     * }
     */
    private array $compilerOptions;

    public function __construct(
        private readonly DiContainerConfigInterface $containerConfig = new DiContainerConfig(),
        private readonly DefinitionsLoaderInterface $definitionsLoader = new DefinitionsLoader(),
    ) {}

    public function load(string ...$file): static
    {
        foreach ($file as $loadConfigFile) {
            $this->loadFiles[] = [
                'override' => false,
                'file' => $loadConfigFile,
            ];
        }

        return $this;
    }

    public function loadOverride(string ...$file): static
    {
        foreach ($file as $loadConfigFile) {
            $this->loadFiles[] = [
                'override' => true,
                'file' => $loadConfigFile,
            ];
        }

        return $this;
    }

    public function addDefinitions(iterable $definitions): static
    {
        $this->definitions[] = [
            'override' => false,
            'definitions' => $definitions,
        ];

        return $this;
    }

    public function addDefinitionsOverride(iterable $definitions): static
    {
        $this->definitions[] = [
            'override' => true,
            'definitions' => $definitions,
        ];

        return $this;
    }

    public function import(string $namespace, string $src, array $excludeFiles = [], array $availableExtensions = ['php']): static
    {
        $this->imports[] = [
            'namespace' => $namespace,
            'src' => $src,
            'exclude_files' => $excludeFiles,
            'available_extensions' => $availableExtensions,
        ];

        return $this;
    }

    /**
     * Note! parameter `$options` may use additional compiler options through option key:
     *
     *      [
     *          // invalid behavior for container compiler
     *          'invalid_behavior' => \Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum::ExceptionOnCompile,
     *          // definitions transformer for container compiler
     *          'di_definition_transformer' => \Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface,
     *          // compiled entries storage
     *          'compiled_entries': \Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface,
     *          // force rebuild compiled container `true` or `false`
     *          'force_rebuild' => false
     *      ]
     *
     * @param array{
     *  invalid_behavior?: InvalidBehaviorCompileEnum,
     *  di_definition_transformer?: DiDefinitionTransformerInterface,
     *  compiled_entries?: CompiledEntriesInterface,
     *  force_rebuild?: bool,
     * } $options
     */
    public function compileToFile(string $outputDirectory, string $containerClass, int $permissionCompiledContainerFile = 0666, bool $isExclusiveLockFile = true, array $options = ['invalid_behavior' => InvalidBehaviorCompileEnum::ExceptionOnCompile]): static
    {
        $this->compilerOutputDirectory = $outputDirectory;
        $this->compilerContainerClass = $containerClass;
        $this->compilerPermissionCompiledContainerFile = $permissionCompiledContainerFile;
        $this->compilerIsExclusiveLockFile = $isExclusiveLockFile;
        $this->compilerOptions = $options;

        return $this;
    }

    public function build(): DiContainerCallInterface&DiContainerInterface&DiContainerSetterInterface
    {
        $this->definitionsLoader->reset();

        if (!isset($this->compilerOutputDirectory)) {
            try {
                return new DiContainer($this->definitions(), $this->containerConfig);
            } catch (ContainerExceptionInterface|DefinitionsLoaderExceptionInterface $e) {
                throw new ContainerBuilderException(
                    sprintf('Cannot build runtime container. Caused by: %s', $e->getMessage()),
                    previous: $e,
                );
            }
        }

        $container = new DiContainer(new DeferredSourceDefinitionsMutable($this->definitions()), $this->containerConfig);
        $diContainerDefinitions = new DiContainerDefinitions($container, new IdsIterator());

        if (!isset($this->compilerDiDefinitionTransformer)) {
            $this->compilerDiDefinitionTransformer = $this->compilerOptions['di_definition_transformer']
                ?? new DiDefinitionTransformer(new FinderClosureCode());
        }

        if (!isset($this->compiledEntries)) {
            $this->compiledEntries = $this->compilerOptions['compiled_entries']
                ?? new CompiledEntries();
        }

        $compiler = new ContainerCompiler(
            $this->compilerContainerClass,
            $diContainerDefinitions,
            $this->compilerDiDefinitionTransformer,
            $this->compilerOptions['invalid_behavior'] ?? InvalidBehaviorCompileEnum::ExceptionOnCompile,
            $this->compiledEntries,
        );

        try {
            $compiledContainerFQN = $compiler->getContainerFQN();
        } catch (InvalidArgumentException $e) {
            throw new ContainerBuilderException(
                sprintf('Invalid class name for compiled container. Parameter $containerClass from method %s::enableCompilation() provide value "%s"', self::class, $this->compilerContainerClass),
                previous: $e,
            );
        }

        $containerCompilerToFile = new ContainerCompilerToFile(
            $this->compilerOutputDirectory,
            $compiler,
            $this->compilerPermissionCompiledContainerFile,
            $this->compilerIsExclusiveLockFile,
        );

        try {
            $file = $containerCompilerToFile->compileToFile($this->compilerOptions['force_rebuild'] ?? false);
        } catch (DefinitionCompileExceptionInterface|RuntimeException $e) {
            throw new ContainerBuilderException(
                sprintf('Cannot compile container. Caused by: %s', $e->getMessage()),
                previous: $e,
            );
        }

        if (file_exists($file) && !class_exists($compiledContainerFQN->getFQN(), false)) {
            require_once $file;
        }

        return new ($compiledContainerFQN->getFQN())(); // @phpstan-ignore return.type
    }

    /**
     * @return Generator<non-empty-string, mixed>
     *
     * @throws ContainerBuilderException|DefinitionsLoaderExceptionInterface
     */
    private function definitions(): Generator
    {
        foreach ($this->imports as $import) {
            try {
                $this->definitionsLoader->import(
                    $import['namespace'],
                    $import['src'],
                    $import['exclude_files'],
                    $import['available_extensions'],
                    $this->containerConfig->isUseAttribute(),
                );
            } catch (DefinitionsLoaderExceptionInterface $e) {
                throw new ContainerBuilderException(
                    sprintf('Cannot build container while import files from directory "%s" with namespace "%s" using method %s::import().', $import['src'], $import['namespace'], self::class),
                    previous: $e,
                );
            }
        }

        foreach ($this->loadFiles as $definitionsFromFile) {
            try {
                if ($definitionsFromFile['override']) {
                    $this->definitionsLoader->loadOverride($definitionsFromFile['file']);
                } else {
                    $this->definitionsLoader->load($definitionsFromFile['file']);
                }
            } catch (DefinitionsLoaderExceptionInterface $e) {
                $useMethod = $definitionsFromFile['override'] ? 'loadOverride()' : 'load()';

                throw new ContainerBuilderException(
                    sprintf('Cannot build container while load configuration from file "%s" using method %s::%s.', $definitionsFromFile['file'], self::class, $useMethod),
                    previous: $e,
                );
            }
        }

        foreach ($this->definitions as $definitions) {
            try {
                $this->definitionsLoader->addDefinitions(
                    $definitions['override'],
                    $definitions['definitions'],
                );
            } catch (ContainerAlreadyRegisteredExceptionInterface|DefinitionsLoaderExceptionInterface $e) {
                $useMethod = $definitions['override']
                    ? 'addDefinitionsOverride()'
                    : 'addDefinitions()';

                throw new ContainerBuilderException(
                    sprintf('Cannot build container while add definition using method %s::%s.', self::class, $useMethod),
                    previous: $e,
                );
            }
        }

        yield from $this->definitionsLoader->definitions();
    }
}
