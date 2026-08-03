<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use ArrayIterator;
use Kaspi\DiContainer\Enum\EventNameEnum;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Exception\NotFoundDefinition;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use UnitEnum;

use function array_key_exists;
use function interface_exists;
use function sprintf;
use function var_export;

/**
 * @internal
 */
final class DefinitionsConfigurator implements DefinitionsConfiguratorInterface
{
    /**
     * @var array<class-string|non-empty-string, mixed>
     */
    private array $cacheOfDefinitions;

    /**
     * @var array<non-empty-string, array<class-string|non-empty-string, DiTaggedDefinitionInterface|DiTaggedObjectDefinitionInterface>>
     */
    private array $cacheOfTaggedDefinitions;

    public function __construct(
        private readonly DefinitionsLoaderInterface $definitionsLoader,
        private readonly ArrayIterator $removedDefinitionIds,
        private readonly ArrayIterator $parameters,
        private readonly ArrayIterator $configuratorContexts,
        private readonly EventListener $definitionsConfiguratorEvent,
    ) {
        // Listeners for a specific event are triggered in `DefinitionsLoader`
        $this->definitionsConfiguratorEvent->on(
            EventNameEnum::ResetCacheOfTaggedDefinitions,
            function (): void {
                unset($this->cacheOfTaggedDefinitions);
            },
        );
    }

    public function reset(): void
    {
        unset($this->cacheOfDefinitions, $this->cacheOfTaggedDefinitions);
    }

    public function removeDefinition(string $id): void
    {
        $this->removedDefinitionIds->offsetSet($id, true);
        unset($this->cacheOfDefinitions[$id], $this->cacheOfTaggedDefinitions);
    }

    public function getDefinitions(): iterable
    {
        yield from $this->definitionsLoader->definitions();
    }

    public function setDefinition(string $id, mixed $definition): void
    {
        $this->definitionsLoader->addDefinitions(true, [$id => $definition]);
        $this->removedDefinitionIds->offsetUnset($id);
        unset($this->cacheOfTaggedDefinitions);
    }

    public function getDefinition(string $id, ?callable $fallback = null): mixed
    {
        if (isset($this->cacheOfDefinitions) && array_key_exists($id, $this->cacheOfDefinitions)) {
            return $this->cacheOfDefinitions[$id];
        }

        foreach ($this->getDefinitions() as $identifier => $definition) {
            $this->cacheOfDefinitions[$identifier] = $definition;

            if ($id === $identifier) {
                return $definition;
            }
        }

        return null !== $fallback
            ? $fallback($id)
            : throw new NotFoundDefinition(id: $id);
    }

    public function findTaggedDefinition(string $tag): iterable
    {
        if (isset($this->cacheOfTaggedDefinitions[$tag])) {
            yield from $this->cacheOfTaggedDefinitions[$tag];
        }

        $useAttribute = null;

        /** @var array<class-string, true> $flippedObjectInterfaceNames */
        $flippedObjectInterfaceNames = [];

        foreach ($this->getDefinitions() as $identifier => $definition) {
            if (!$definition instanceof DiTaggedDefinitionInterface) {
                continue;
            }

            if ($definition instanceof DiTaggedObjectDefinitionInterface) {
                foreach ($definition->getInterfaceNames() as $interfaceName) {
                    if (!isset($this->cacheOfTaggedDefinitions[$interfaceName][$identifier])) {
                        $this->cacheOfTaggedDefinitions[$interfaceName][$identifier] = $definition;
                        $flippedObjectInterfaceNames[$interfaceName] = true;
                    }
                }

                $useAttribute ??= $this->definitionsLoader->isUseAttribute();

                /*
                 * Tag bound via php attribute.
                 * 🚩 The documentation says that PHP attributes have higher priority than PHP definitions.
                 */
                if ($useAttribute) {
                    foreach ($definition->getTagsByAttribute() as $tagName => $options) {
                        if (!isset($this->cacheOfTaggedDefinitions[$tagName][$identifier])) {
                            $isInterface = isset($flippedObjectInterfaceNames[$tagName]);

                            if (!$isInterface && interface_exists($tagName)) {
                                $isInterface = true;
                                $flippedObjectInterfaceNames[$tagName] = true;
                            }

                            // The tag name, represented as a php interface, must be excluded from the valid tag name.
                            if (!$isInterface) {
                                $this->cacheOfTaggedDefinitions[$tagName][$identifier] = $definition;
                            }
                        }
                    }
                }

                foreach ($definition->getBoundTags() as $tagName => $options) {
                    // The tag name, represented as a php interface, must be excluded from the valid tag name.
                    $isInterface = isset($flippedObjectInterfaceNames[$tagName]);

                    if ($isInterface || isset($this->cacheOfTaggedDefinitions[$tagName][$identifier])) {
                        continue;
                    }

                    // The tag name, represented as a php interface, must be excluded from the valid tag name.
                    if (interface_exists($tagName)) {
                        $flippedObjectInterfaceNames[$tagName] = true;

                        continue;
                    }

                    $this->cacheOfTaggedDefinitions[$tagName][$identifier] = $definition;
                }
            } else {
                foreach ($definition->getTags() as $tagName => $options) {
                    if (!isset($this->cacheOfTaggedDefinitions[$tagName][$identifier])) {
                        $this->cacheOfTaggedDefinitions[$tagName][$identifier] = $definition;
                    }
                }
            }

            if (isset($this->cacheOfTaggedDefinitions[$tag][$identifier])) {
                yield $identifier => $definition;
            }
        }

        unset($flippedObjectInterfaceNames);
    }

    public function load(string $file, string ...$_): void
    {
        $this->definitionsLoader->load($file, ...$_);
    }

    public function loadOverride(string $file, string ...$_): void
    {
        $this->definitionsLoader->loadOverride($file, ...$_);
    }

    public function loadParameters(string $file, string ...$_): void
    {
        $this->definitionsLoader->loadParameters($file, ...$_);
    }

    public function addParameters(iterable $parameters): void
    {
        $this->definitionsLoader->addParameters($parameters);
    }

    public function setParameter(string $name, array|bool|float|int|string|UnitEnum|null $value): void
    {
        $this->definitionsLoader->addParameters([$name => $value]);
    }

    public function removeParameter(string $name): void
    {
        $this->parameters->offsetUnset($name);
    }

    public function hasParameter(string $name): bool
    {
        return $this->parameters->offsetExists($name);
    }

    public function getContext(string $name, ?callable $fallback = null): mixed
    {
        if ($this->configuratorContexts->offsetExists($name)) {
            return $this->configuratorContexts->offsetGet($name);
        }

        if (null !== $fallback) {
            return ($fallback)($name);
        }

        throw new DefinitionsLoaderException(sprintf('The context name %s does not exist.', var_export($name, true)));
    }
}
