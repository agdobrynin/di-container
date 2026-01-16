<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Compiler\ContainerCompilerToFile;
use Kaspi\DiContainer\Compiler\DiContainerDefinitions;
use Kaspi\DiContainer\Compiler\DiDefinitionTransformer;
use Kaspi\DiContainer\Compiler\IdsIterator;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\ContainerBuilderException;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\ContainerBuilderInterface;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionIdentifierInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;
use Kaspi\DiContainer\SourceDefinitions\DeferredSourceDefinitionsMutable;

use function class_exists;
use function file_exists;
use function sprintf;

final class ContainerBuilder implements ContainerBuilderInterface
{
    private DiContainerConfigInterface $diContainerConfig;

    /**
     * @var list<array{
     *  override: bool,
     *  file: non-empty-string
     * }>
     */
    private array $loadFiles;

    /**
     * @var list<array{
     *  namespace: non-empty-string,
     *  src: non-empty-string,
     *  exclude_files: list<non-empty-string>,
     *  available_extensions: list<non-empty-string>,
     * }>
     */
    private array $imports;

    /**
     * @var list<array{
     *  override: bool,
     *  definitions: iterable<non-empty-string|non-negative-int, DiDefinitionIdentifierInterface|mixed>
     * }>
     */
    private array $definitions;

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

    /**
     * @var array{
     *  invalid_behavior?: InvalidBehaviorCompileEnum,
     *  finder_closure?: FinderClosureCodeInterface,
     *  force_rebuild?: bool,
     * }
     */
    private array $compilerOptions;

    public function __construct(private readonly DefinitionsLoaderInterface $definitionsLoader = new DefinitionsLoader()) {}

    public function setDiContainerConfig(DiContainerConfigInterface $config): static
    {
        $this->diContainerConfig = $config;

        return $this;
    }

    public function getDiContainerConfig(): DiContainerConfigInterface
    {
        return $this->diContainerConfig;
    }

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

    public function addDefinitions(bool $overrideDefinitions, iterable $definitions): static
    {
        $this->definitions[] = [
            'override' => $overrideDefinitions,
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
     *          // closure parser
     *          'finder_closure' => \Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface,
     *          // force rebuild compiled container `true` or `false`
     *          'force_rebuild' => false
     *      ]
     *
     * @param array{
     *  invalid_behavior?: InvalidBehaviorCompileEnum,
     *  finder_closure?: FinderClosureCodeInterface,
     *  force_rebuild?: bool,
     * } $options
     */
    public function enableCompilation(string $outputDirectory, string $containerClass, int $permissionCompiledContainerFile = 0666, bool $isExclusiveLockFile = true, array $options = ['invalid_behavior' => InvalidBehaviorCompileEnum::ExceptionOnCompile]): static
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
        $this->diContainerConfig ??= new DiContainerConfig();

        if (!isset($this->compilerOutputDirectory)) {
            return new DiContainer($this->definitions(), $this->diContainerConfig);
        }

        $container = new DiContainer(new DeferredSourceDefinitionsMutable($this->definitions()), $this->diContainerConfig);
        $diContainerDefinitions = new DiContainerDefinitions($container, new IdsIterator());

        $diDefinitionTransformer = new DiDefinitionTransformer(
            $this->compilerOptions['finder_closure'] ?? new FinderClosureCode()
        );

        $compiler = new ContainerCompiler(
            $this->compilerContainerClass,
            $diContainerDefinitions,
            $diDefinitionTransformer,
            $this->compilerOptions['invalid_behavior'] ?? InvalidBehaviorCompileEnum::ExceptionOnCompile,
        );

        try {
            $compiledContainerFQN = $compiler->getContainerFQN();
        } catch (InvalidArgumentException $e) {
            throw new ContainerBuilderException(
                sprintf('Invalid class name for compiled container. Parameter $containerClass from method ContainerBuilder::enableCompilation() provide value "%s"', $this->compilerContainerClass),
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
        } catch (DefinitionCompileExceptionInterface $e) {
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
                    $this->diContainerConfig->isUseAttribute(),
                );
            } catch (DefinitionsLoaderExceptionInterface $e) {
                throw new ContainerBuilderException(
                    sprintf('Cannot build container while import files from directory "%s" with namespace "%s" using method ContainerBuilder::import().', $import['src'], $import['namespace']),
                    previous: $e,
                );
            }
        }

        foreach ($this->loadFiles as $definitionsFromFile) {
            if ($definitionsFromFile['override']) {
                try {
                    $this->definitionsLoader->loadOverride($definitionsFromFile['file']);
                } catch (DefinitionsLoaderExceptionInterface $e) {
                    throw new ContainerBuilderException(
                        sprintf('Cannot build container while load configuration with override from file "%s" using method ContainerBuilder::loadOverride().', $definitionsFromFile['file']),
                        previous: $e,
                    );
                }
            } else {
                try {
                    $this->definitionsLoader->load($definitionsFromFile['file']);
                } catch (DefinitionsLoaderExceptionInterface $e) {
                    throw new ContainerBuilderException(
                        sprintf('Cannot build container while load configuration from file "%s" using method ContainerBuilder::load().', $definitionsFromFile['file']),
                        previous: $e,
                    );
                }
            }
        }

        foreach ($this->definitions as $definitions) {
            try {
                $this->definitionsLoader->addDefinitions(
                    $definitions['override'],
                    $definitions['definitions'],
                );
            } catch (ContainerAlreadyRegisteredExceptionInterface|DefinitionsLoaderExceptionInterface $e) {
                $with = $definitions['override'] ? 'with override' : 'without override';

                throw new ContainerBuilderException(
                    sprintf('Cannot build container while add definition %s using method ContainerBuilder::addDefinitions().', $with),
                    previous: $e,
                );
            }
        }

        yield from $this->definitionsLoader->definitions();
    }
}
