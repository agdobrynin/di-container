<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Generator;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledContainerFQN;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\ContainerCompilerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerInterface;

use function ltrim;
use function ob_get_clean;
use function ob_start;
use function strrpos;
use function substr;

final class ContainerCompiler implements ContainerCompilerInterface
{
    /**
     * @var non-empty-array<non-empty-string, array{0: non-empty-string, 1:CompiledEntryInterface}>
     */
    private array $mapContainerIdToMethod; // @phpstan-ignore property.onlyWritten

    private CompiledContainerFQN $compiledContainerFQN;

    /**
     * @param non-empty-string $outputDirectory
     * @param class-string     $containerClass  container class as fully qualified name
     */
    public function __construct(
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly string $outputDirectory,
        private readonly string $containerClass,
        private readonly DiDefinitionTransformerInterface $definitionTransform,
    ) {}

    public function getOutputDirectory(): string
    {
        // todo make function for validate directory - must exist and be writable.
        return $this->outputDirectory;
    }

    public function getContainerFQN(): CompiledContainerFQN
    {
        if (isset($this->compiledContainerFQN)) {
            return $this->compiledContainerFQN;
        }

        $pos = strrpos($this->containerClass, '\\');

        /** @var class-string $class */
        $class = false === $pos ? $this->containerClass : substr($this->containerClass, $pos + 1);
        $namespace = false === $pos ? '' : ltrim(substr($this->containerClass, 0, $pos), '\\');

        return $this->compiledContainerFQN = new class($namespace, $class) implements CompiledContainerFQN {
            private string $fqn;

            /**
             * @param class-string $class
             */
            public function __construct(private readonly string $namespace, private readonly string $class) {}

            public function getNamespace(): string
            {
                return $this->namespace;
            }

            public function getClass(): string
            {
                return $this->class;
            }

            public function getFQN(): string
            {
                return $this->fqn ??= '' !== $this->namespace
                    ? '\\'.$this->namespace.'\\'.$this->class
                    : '\\'.$this->class;
            }
        };
    }

    public function compile(): string
    {
        $containerEntry = new CompiledEntry('$this', null, '', '', [], 'self');

        $this->mapContainerIdToMethod = [
            ContainerInterface::class => ['getPsrContainer', $containerEntry],
            DiContainerInterface::class => ['getDiContainerInterface', $containerEntry],
            DiContainer::class => ['getDiContainer', $containerEntry],
        ];

        $num = 0;

        foreach ($this->diContainerDefinitions->getDefinitions() as $id => $definition) {
            $compiledEntity = $this->definitionTransform
                ->transform($definition, $this->diContainerDefinitions)
                ->compile('$this', context: $definition)
            ;

            // TODO how about name generator for method name in container.
            $serviceMethod = 'getService'.++$num;

            $this->mapContainerIdToMethod[$id] = [$serviceMethod, $compiledEntity];
        }

        ob_start();

        require __DIR__.'/template.php';

        return (string) ob_get_clean();
    }
}
