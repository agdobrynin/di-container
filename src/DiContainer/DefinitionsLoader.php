<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedClassName;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedClassNameInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClassInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Psr\Container\ContainerExceptionInterface;

final class DefinitionsLoader implements DefinitionsLoaderInterface
{
    use DefinitionIdentifierTrait;
    use AttributeReaderTrait;
    use DiContainerTrait;

    private \ArrayIterator $configDefinitions;

    /**
     * @var array<non-empty-string, FinderFullyQualifiedClassNameInterface>
     */
    private array $import;

    public function __construct()
    {
        $this->configDefinitions = new \ArrayIterator();
    }

    public function load(string ...$file): static
    {
        $this->loadFormFile(false, ...$file);

        return $this;
    }

    public function loadOverride(string ...$file): static
    {
        $this->loadFormFile(true, ...$file);

        return $this;
    }

    public function addDefinitions(bool $overrideDefinitions, iterable $definitions): static
    {
        $itemCount = 0;

        foreach ($definitions as $identifier => $definition) {
            try {
                /** @var class-string|non-empty-string $identifier */
                $identifier = $this->getIdentifier($identifier, $definition);
            } catch (DiDefinitionExceptionInterface $e) {
                throw new DiDefinitionException(
                    message: \sprintf('%s Item position #%d.', $e->getMessage(), $itemCount),
                    previous: $e
                );
            }

            if (!$overrideDefinitions && $this->configDefinitions->offsetExists($identifier)) {
                throw new ContainerAlreadyRegisteredException(
                    \sprintf(
                        'Definition with identifier "%s" is already registered. Item position #%d.',
                        $identifier,
                        $itemCount
                    )
                );
            }

            $this->configDefinitions->offsetSet($identifier, $definition);
            ++$itemCount;
        }

        return $this;
    }

    public function definitions(): iterable
    {
        $this->configDefinitions->rewind();

        if (isset($this->import)) {
            $iterator = new \AppendIterator();

            foreach ($this->import as $finderClass) {
                $iterator->append($finderClass->find());
            }

            /** @var class-string $class */
            foreach ($iterator as $class) {
                $reflectionClass = new \ReflectionClass($class);

                if ($this->isAutowireExclude($reflectionClass)) {
                    continue;
                }

                foreach ($this->getAutowireAttribute($reflectionClass) as $attribute) {
                    $identifier = '' !== $attribute->getIdentifier()
                        ? $attribute->getIdentifier()
                        : $class;

                    if ($this->configDefinitions->offsetExists($identifier)) {
                        throw new DiDefinitionException(
                            \sprintf('Cannot automatically set definition for container identifier "%s". Configure class "%s" via php attribute or via config file.', $identifier, $class)
                        );
                    }

                    yield $identifier => \Kaspi\DiContainer\diAutowire($class, $attribute->isSingleton());
                }

                if (!$this->configDefinitions->offsetExists($class)) {
                    yield $class => \Kaspi\DiContainer\diAutowire($class);
                }
            }
        }

        yield from $this->configDefinitions; // @phpstan-ignore generator.keyType
    }

    public function import(string $namespace, string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php']): static
    {
        if (isset($this->import[$namespace])) {
            throw new \InvalidArgumentException(
                \sprintf('Namespace "%s" is already imported.', $namespace)
            );
        }

        $this->import[$namespace] = (new FinderFullyQualifiedClassName(
            $namespace,
            (new FinderFile($src, $excludeFilesRegExpPattern, $availableExtensions))->getFiles()
        ));

        return $this;
    }

    private function getIteratorFromFile(string $srcFile): \Generator
    {
        \ob_start();
        $content = require $srcFile;
        \ob_get_clean();

        return match (true) {
            \is_iterable($content) => yield from $content,
            $content instanceof \Closure && \is_iterable($content()) => yield from $content(),
            default => throw new \InvalidArgumentException(
                \sprintf('File "%s" return not valid format. File must be use "return" keyword, and return any iterable type or callback function using "yield" keyword.', $srcFile)
            )
        };
    }

    private function loadFormFile(bool $overrideDefinitions, string ...$file): void
    {
        foreach ($file as $srcFile) {
            if (!\file_exists($srcFile) || !\is_readable($srcFile)) {
                throw new \InvalidArgumentException(\sprintf('The file "%s" does not exist or is not readable', $srcFile));
            }

            try {
                $this->addDefinitions($overrideDefinitions, $this->getIteratorFromFile($srcFile));
                unset($srcFile);
            } catch (ContainerExceptionInterface|DiDefinitionExceptionInterface $e) {
                throw new ContainerException(
                    \sprintf('Invalid definition in file "%s". Reason: %s', $srcFile, $e->getMessage())
                );
            }
        }
    }
}
