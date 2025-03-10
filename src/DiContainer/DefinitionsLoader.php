<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Psr\Container\ContainerExceptionInterface;

/**
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class DefinitionsLoader implements DefinitionsLoaderInterface
{
    use DefinitionIdentifierTrait;
    use AttributeReaderTrait;

    private \ArrayIterator $configDefinitions;

    /**
     * @var array<non-empty-string, array{finderFQN: FinderFullyQualifiedNameInterface, useAttribute: bool}>
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
            /** @var FinderFullyQualifiedNameInterface $finderFQN */
            foreach ($this->import as ['finderFQN' => $finderFQN, 'useAttribute' => $useAttribute]) {
                /** @var ItemFQN $itemFQN */
                foreach ($finderFQN->find() as $itemFQN) {
                    if ([] !== ($definition = $this->makeDefinitionFromReflectionClass($itemFQN, $useAttribute))) {
                        yield from $definition;
                    }
                }
            }
        }

        yield from $this->configDefinitions; // @phpstan-ignore generator.keyType
    }

    public function import(string $namespace, string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php'], bool $useAttribute = true): static
    {
        if (isset($this->import[$namespace])) {
            throw new \InvalidArgumentException(
                \sprintf('Namespace "%s" is already imported.', $namespace)
            );
        }

        $finderFQN = new FinderFullyQualifiedName(
            $namespace,
            (new FinderFile($src, $excludeFilesRegExpPattern, $availableExtensions))->getFiles()
        );

        $this->import[$namespace] = ['finderFQN' => $finderFQN, 'useAttribute' => $useAttribute];

        return $this;
    }

    /**
     * @param ItemFQN $itemFQN
     *
     * @return array<class-string|non-empty-string, DiDefinitionAutowire|DiDefinitionGet>
     */
    private function makeDefinitionFromReflectionClass(array $itemFQN, bool $useAttribute): array
    {
        ['fqn' => $fqn, 'tokenId' => $tokenId] = $itemFQN;

        if (!$useAttribute) {
            return $this->configDefinitions->offsetExists($fqn) || \T_INTERFACE === $tokenId
                ? []
                : [$fqn => new DiDefinitionAutowire($fqn)];
        }

        $reflectionClass = new \ReflectionClass($fqn);

        if ($this->isAutowireExclude($reflectionClass)) {
            return [];
        }

        if ($reflectionClass->isInterface()) {
            $service = $this->getServiceAttribute($reflectionClass);

            if (null === $service) {
                return [];
            }

            if ($this->configDefinitions->offsetExists($reflectionClass->name)) {
                throw new DiDefinitionException(
                    \sprintf('Cannot automatically set definition via #[%s] attribute for container identifier "%s". Configure class "%s" via php attribute or via config file.', Service::class, $reflectionClass->name, $reflectionClass->name)
                );
            }

            return [$reflectionClass->name => new DiDefinitionGet($service->getIdentifier())];
        }

        if (($autowireAttrs = $this->getAutowireAttribute($reflectionClass))->valid()) {
            $services = [];

            foreach ($autowireAttrs as $autowireAttr) {
                if ($this->configDefinitions->offsetExists($autowireAttr->getIdentifier())) {
                    throw new DiDefinitionException(
                        \sprintf('Cannot automatically set definition via #[%s] attribute for container identifier "%s". Configure class "%s" via php attribute or via config file.', Autowire::class, $autowireAttr->getIdentifier(), $reflectionClass->name)
                    );
                }

                $services[$autowireAttr->getIdentifier()] = new DiDefinitionAutowire($reflectionClass->name, $autowireAttr->isSingleton());
            }

            return $services; // @phpstan-ignore return.type
        }

        return $this->configDefinitions->offsetExists($reflectionClass->name)
            ? []
            : [$reflectionClass->name => new DiDefinitionAutowire($reflectionClass->name)];
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
