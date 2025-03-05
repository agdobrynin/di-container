<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedClassName;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedClassNameInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Psr\Container\ContainerExceptionInterface;

final class DefinitionsLoader implements DefinitionsLoaderInterface
{
    use DefinitionIdentifierTrait;
    use AttributeReaderTrait;

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
            foreach ($this->import as $finderClass) {
                /** @var class-string $classOrInterface */
                foreach ($finderClass->find() as $classOrInterface) {
                    if ($this->configDefinitions->offsetExists($classOrInterface)) {
                        continue;
                    }

                    $reflectionClass = new \ReflectionClass($classOrInterface);

                    if ($this->isAutowireExclude($reflectionClass)) {
                        continue;
                    }

                    if ([] !== ($definitions = $this->makeDefinitionFromReflectionClass($reflectionClass))) {
                        yield from $definitions;
                    }
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

    /**
     * @return array<non-empty-string, DiDefinitionAutowire|DiDefinitionInterface>
     */
    private function makeDefinitionFromReflectionClass(\ReflectionClass $reflectionClass): array
    {
        if ($reflectionClass->isInterface()) {
            if (null === ($service = $this->getServiceAttribute($reflectionClass))) {
                return [];
            }

            return [$reflectionClass->name => \Kaspi\DiContainer\diGet($service->getIdentifier())];
        }

        if (($autowireAttrs = $this->getAutowireAttribute($reflectionClass))->valid()) {
            $services = [];

            foreach ($autowireAttrs as $autowireAttr) {
                if ($this->configDefinitions->offsetExists($autowireAttr->getIdentifier())) {
                    throw new DiDefinitionException(
                        \sprintf('Cannot automatically set definition for container identifier "%s". Configure class "%s" via php attribute or via K file.', $autowireAttr->getIdentifier(), $reflectionClass->name)
                    );
                }

                $services[$autowireAttr->getIdentifier()] = new DiDefinitionAutowire($reflectionClass, $autowireAttr->isSingleton());
            }

            return $services; // @phpstan-ignore return.type
        }

        return [$reflectionClass->name => new DiDefinitionAutowire($reflectionClass)];
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
