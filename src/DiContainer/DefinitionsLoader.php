<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use ArrayIterator;
use Error;
use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\AutowireExclude;
use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Exception\DefinitionsLoaderInvalidArgumentException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\FinderFullyQualifiedNameCollectionInterface;
use ParseError;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

use function class_exists;
use function file_exists;
use function in_array;
use function interface_exists;
use function is_callable;
use function is_iterable;
use function is_readable;
use function ob_get_clean;
use function ob_start;
use function sprintf;
use function var_export;

use const T_CLASS;
use const T_INTERFACE;

/**
 * @internal
 *
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class DefinitionsLoader implements DefinitionsLoaderInterface
{
    /** @var ArrayIterator<non-empty-string, mixed> */
    private readonly ArrayIterator $configuredDefinitions;

    /** @var ArrayIterator<class-string|non-empty-string, true> */
    private readonly ArrayIterator $removedDefinitionIds;

    private DefinitionsConfiguratorInterface $definitionsConfigurator;

    /**
     * Have the excluded definition been imported using the `import()` method.
     */
    private bool $isRemovedDefinitionImport = false;

    private bool $useAttribute = true;

    /** @var array<non-empty-string, DiDefinitionAutowire|DiDefinitionFactory|DiDefinitionGet> */
    private array $importedDefinitions;

    /**
     * Circular watcher for load definitions from files.
     *
     * @var array<string, true>
     */
    private array $circularLoadFromFileWatcher = [];

    public function __construct(
        private ?FinderFullyQualifiedNameCollectionInterface $finderFullyQualifiedNameCollection = null,
    ) {
        $this->configuredDefinitions = new ArrayIterator();
        $this->removedDefinitionIds = new ArrayIterator();
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
                $identifier = Helper::getContainerIdentifier($identifier, $definition);
            } catch (ContainerIdentifierExceptionInterface $e) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    message: sprintf('%s Item position #%d.', $e->getMessage(), $itemCount),
                    previous: $e
                );
            }

            if (!$overrideDefinitions && $this->configuredDefinitions->offsetExists($identifier)) {
                throw new ContainerAlreadyRegisteredException(
                    sprintf(
                        'Definition with identifier "%s" is already registered in container. Item position #%d.',
                        $identifier,
                        $itemCount
                    )
                );
            }

            $this->configuredDefinitions->offsetSet($identifier, $definition);
            $this->removedDefinitionIds->offsetUnset($identifier);
            ++$itemCount;
        }

        return $this;
    }

    /**
     * Note: parameter `$excludeFiles` use php function `\fnmatch()`, detail info about file pattern see in documentation.
     *
     * @see https://www.php.net/manual/en/function.fnmatch.php
     */
    public function import(string $namespace, string $src, array $excludeFiles = [], array $availableExtensions = ['php']): static
    {
        if (null === $this->finderFullyQualifiedNameCollection) {
            $this->finderFullyQualifiedNameCollection = new FinderFullyQualifiedNameCollection();
        }

        try {
            $this->finderFullyQualifiedNameCollection->add(
                new FinderFullyQualifiedName(
                    $namespace,
                    new FinderFile($src, $excludeFiles, $availableExtensions)
                )
            );
        } catch (InvalidArgumentException $e) {
            throw new DefinitionsLoaderInvalidArgumentException(
                sprintf('Cannot import fully qualified names for php class or interface from source directory "%s" with namespace "%s". Reason: %s', $src, $namespace, $e->getMessage()),
                previous: $e
            );
        }

        unset($this->importedDefinitions);
        $this->isRemovedDefinitionImport = false;

        return $this;
    }

    public function useAttribute(bool $useAttribute): static
    {
        $this->useAttribute = $useAttribute;

        return $this;
    }

    public function isUseAttribute(): bool
    {
        return $this->useAttribute;
    }

    public function definitionsConfigurator(): DefinitionsConfiguratorInterface
    {
        return $this->definitionsConfigurator ??= new class($this, $this->removedDefinitionIds) implements DefinitionsConfiguratorInterface {
            public function __construct(
                private readonly DefinitionsLoaderInterface $definitionsLoader,
                private readonly ArrayIterator $removedDefinitionIds,
            ) {}

            public function removeDefinition(string $id): void
            {
                $this->removedDefinitionIds->offsetSet($id, true);
            }

            public function getDefinitions(): iterable
            {
                yield from $this->definitionsLoader->definitions();
            }

            public function setDefinition(string $id, mixed $definition): void
            {
                $this->definitionsLoader->addDefinitions(true, [$id => $definition]);
                $this->removedDefinitionIds->offsetUnset($id);
            }

            public function getDefinition(string $id): ?DiDefinitionInterface
            {
                foreach ($this->getDefinitions() as $identifier => $definition) {
                    if ($definition instanceof DiDefinitionInterface && $id === $identifier) {
                        return $definition;
                    }
                }

                return null;
            }

            public function findTaggedDefinition(string $tag): iterable
            {
                $tagIsInterface = null;

                foreach ($this->getDefinitions() as $identifier => $definition) {
                    if (!$definition instanceof DiTaggedDefinitionInterface) {
                        continue;
                    }

                    $hasTagOnAutowire = false;

                    if ($definition instanceof DiDefinitionAutowire) {
                        $tagIsInterface ??= interface_exists($tag);
                        $hasTagOnAutowire = $tagIsInterface && $definition->getDefinition()->implementsInterface($tag);

                        if (!$tagIsInterface && !$hasTagOnAutowire) {
                            $hasTagOnAutowire = ($this->definitionsLoader->isUseAttribute() && isset($definition->getTagsByAttribute()[$tag]))
                                || isset($definition->getBoundTags()[$tag]);
                        }

                        if (!$hasTagOnAutowire) {
                            continue;
                        }
                    }

                    if ($hasTagOnAutowire || $definition->hasTag($tag)) {
                        yield $identifier => $definition;
                    }
                }
            }

            public function load(string $file, string ...$_): void
            {
                $this->definitionsLoader->load($file, ...$_);
            }

            public function loadOverride(string $file, string ...$_): void
            {
                $this->definitionsLoader->loadOverride($file, ...$_);
            }
        };
    }

    public function definitions(): iterable
    {
        yield from $this->importedDefinitions();

        yield from $this->configuredDefinitions;
    }

    public function removedDefinitionIds(): iterable
    {
        if ($this->isRemovedDefinitionImport) {
            yield from $this->removedDefinitionIds;

            return;
        }

        if (null === $this->finderFullyQualifiedNameCollection) {
            $this->isRemovedDefinitionImport = true;

            yield from $this->removedDefinitionIds;

            return;
        }

        foreach ($this->finderFullyQualifiedNameCollection->get() as $finderFQN) {
            $fQCNExcluded = $finderFQN->getExcluded();

            do {
                try {
                    /** @var ItemFQN $itemFQN */
                    $itemFQN = $fQCNExcluded->current();

                    if (null === $itemFQN) {
                        break;
                    }

                    ['fqn' => $fqn] = $itemFQN;

                    $this->removedDefinitionIds->offsetSet($fqn, true);
                } catch (InvalidArgumentException|RuntimeException $e) {
                    throw new DefinitionsLoaderException(
                        sprintf('Cannot get fully qualified name for excluded php class or interface from source directory "%s" with namespace "%s". Reason: %s', $finderFQN->getFinderFile()->getSrc(), $finderFQN->getNamespace(), $e->getMessage()),
                        previous: $e
                    );
                }

                $fQCNExcluded->next();
            } while ($fQCNExcluded->valid());
        }

        while (null !== ($identifier = $this->removedDefinitionIds->key())) {
            if (isset($this->configuredDefinitions[$identifier])) {
                unset($this->removedDefinitionIds[$identifier]);
            }

            $this->removedDefinitionIds->next();
        }

        $this->isRemovedDefinitionImport = true;

        yield from $this->removedDefinitionIds;
    }

    public function reset(): void
    {
        while ($this->configuredDefinitions->valid()) {
            $this->configuredDefinitions->offsetUnset($this->configuredDefinitions->key());
        }

        while ($this->removedDefinitionIds->valid()) {
            $this->removedDefinitionIds->offsetUnset($this->removedDefinitionIds->key());
        }

        $this->useAttribute = true;
        $this->finderFullyQualifiedNameCollection?->reset();
        unset($this->importedDefinitions);
        $this->isRemovedDefinitionImport = false;
        $this->circularLoadFromFileWatcher = [];
    }

    /**
     * @return Generator<non-empty-string, DiDefinitionAutowire|DiDefinitionFactory|DiDefinitionGet>
     *
     * @throws DefinitionsLoaderException
     */
    private function importedDefinitions(): Generator
    {
        if (null === $this->finderFullyQualifiedNameCollection) {
            return;
        }

        if (isset($this->importedDefinitions)) {
            yield from $this->importedDefinitions;

            return;
        }

        $this->importedDefinitions = [];

        foreach ($this->finderFullyQualifiedNameCollection->get() as $finderFQN) {
            $fQCNMatched = $finderFQN->getMatched();

            do {
                try {
                    /** @var null|ItemFQN $itemFQN */
                    $itemFQN = $fQCNMatched->current();

                    if (null === $itemFQN) {
                        break;
                    }
                } catch (InvalidArgumentException|RuntimeException $e) {
                    throw new DefinitionsLoaderException(
                        sprintf('Cannot get fully qualified name for php class or interface from source directory "%s" with namespace "%s". Reason: %s', $finderFQN->getFinderFile()->getSrc(), $finderFQN->getNamespace(), $e->getMessage()),
                        previous: $e
                    );
                }

                try {
                    $definitions = $this->makeDefinitionFromItemFQN($itemFQN);
                } catch (AutowireAttributeException|AutowireParameterTypeException|DefinitionsLoaderInvalidArgumentException $e) {
                    throw new DefinitionsLoaderException(
                        sprintf('Cannot make container definition from source directory "%s" with namespace "%s". The fully qualified name "%s" in file %s:%d. Reason: %s', $finderFQN->getFinderFile()->getSrc(), $finderFQN->getNamespace(), $itemFQN['fqn'], $itemFQN['file'], $itemFQN['line'] ?? 0, $e->getMessage()),
                        previous: $e
                    );
                }

                foreach ($definitions as $identifier => $definition) {
                    if ($definition instanceof DiDefinitionAutowire
                        && isset($this->importedDefinitions[$identifier])
                        && $this->importedDefinitions[$identifier] instanceof DiDefinitionAutowire) {
                        throw new DefinitionsLoaderException(
                            sprintf('Container identifier "%s" already import for class "%s". Please specify container identifier for class "%s".', $identifier, $this->importedDefinitions[$identifier]->getIdentifier(), $definition->getIdentifier())
                        );
                    }

                    $this->importedDefinitions[$identifier] = $definition;
                }

                $fQCNMatched->next();
            } while ($fQCNMatched->valid());
        }

        /*
         * Check valid DiDefinitionGet for imported definitions.
         * Interface maybe configured via php attribute #[Service('container_id')].
        */
        foreach ($this->importedDefinitions as $identifier => $definition) {
            if (!$definition instanceof DiDefinitionGet) {
                continue;
            }

            $containerIdentifier = $definition->getDefinition();

            if (!isset($this->importedDefinitions[$containerIdentifier])
                && !$this->configuredDefinitions->offsetExists($containerIdentifier)) {
                throw new DefinitionsLoaderException(
                    sprintf('The container identifier "%s" is not registered. The reference from the definition with the id "%s".', $containerIdentifier, $identifier)
                );
            }
        }

        yield from $this->importedDefinitions;
    }

    /**
     * @param ItemFQN $itemFQN
     *
     * @return array<class-string|non-empty-string, DiDefinitionAutowire|DiDefinitionFactory|DiDefinitionGet>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException|DefinitionsLoaderInvalidArgumentException
     */
    private function makeDefinitionFromItemFQN(array $itemFQN): array
    {
        ['fqn' => $fqn, 'tokenId' => $tokenId] = $itemFQN;

        if (!in_array($tokenId, [T_INTERFACE, T_CLASS], true)) {
            throw new DefinitionsLoaderInvalidArgumentException(
                sprintf('Unsupported token id. Support only T_INTERFACE with id %d, T_CLASS with id %d. Got %s.', T_INTERFACE, T_CLASS, var_export($tokenId, true))
            );
        }

        if ($this->removedDefinitionIds->offsetExists($fqn)) {
            return [];
        }

        if (!$this->useAttribute) {
            return $this->configuredDefinitions->offsetExists($fqn) || T_INTERFACE === $tokenId
                ? []
                : [$fqn => new DiDefinitionAutowire($fqn)];
        }

        try {
            $reflectionClass = new ReflectionClass($fqn);
        } catch (Error|ReflectionException $e) { // @phpstan-ignore catch.neverThrown, catch.neverThrown
            throw new DefinitionsLoaderInvalidArgumentException(
                message: sprintf(
                    'Get fully qualified name "%s" from file "%s:%d" (line #%d). Reason: %s',
                    $fqn,
                    $itemFQN['file'],
                    $itemFQN['line'],
                    $itemFQN['line'],
                    $e->getMessage()
                ),
                previous: $e
            );
        }

        if (AttributeReader::isAutowireExclude($reflectionClass)) {
            if ($this->configuredDefinitions->offsetExists($reflectionClass->name)) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('Cannot automatically configure class "%s". The class mark as excluded via php attribute "%s". This class "%s" must be configure via php attribute or via config file.', $reflectionClass->name, AutowireExclude::class, $reflectionClass->name)
                );
            }

            return [];
        }

        if ($reflectionClass->isInterface()) {
            $service = AttributeReader::getServiceAttribute($reflectionClass);

            if (null === $service) {
                return [];
            }

            if ($this->configuredDefinitions->offsetExists($reflectionClass->name)) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('Cannot automatically configure interface "%s" via php attribute "%s". Container identifier "%s" already registered. This interface "%s" must be configure via php attribute or via config file.', $reflectionClass->name, Service::class, $reflectionClass->name, $reflectionClass->name)
                );
            }

            return [
                $reflectionClass->name => class_exists($service->id)
                    ? new DiDefinitionAutowire($service->id, $service->isSingleton)
                    : new DiDefinitionGet($service->id),
            ];
        }

        if (($autowireAttrs = AttributeReader::getAutowireAttribute($reflectionClass))->valid()) {
            $services = [];

            foreach ($autowireAttrs as $autowireAttr) {
                if ($this->configuredDefinitions->offsetExists($autowireAttr->id)) {
                    throw new DefinitionsLoaderInvalidArgumentException(
                        sprintf('Cannot automatically configure class "%s" via php attribute "%s". Container identifier "%s" already registered. This class "%s" must be configure via php attribute or via config file.', $reflectionClass->name, Autowire::class, $autowireAttr->id, $reflectionClass->name)
                    );
                }

                $services[$autowireAttr->id] = (new DiDefinitionAutowire($reflectionClass->name, $autowireAttr->isSingleton))
                    ->bindArguments(...$autowireAttr->arguments)
                ;
            }

            return $services; // @phpstan-ignore return.type
        }

        if (null !== ($factory = AttributeReader::getDiFactoryAttributeOnClass($reflectionClass))) {
            if ($this->configuredDefinitions->offsetExists($reflectionClass->name)) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('Cannot automatically configure class "%s" via php attribute "%s". The class "%s" already registered. This class "%s" must be configure via php attribute or via config file.', $reflectionClass->name, DiFactory::class, $reflectionClass->name, $reflectionClass->name)
                );
            }

            $diFactory = new DiDefinitionFactory($factory->definition, $factory->isSingleton);

            return [$reflectionClass->name => $diFactory->bindArguments(...$factory->arguments)];
        }

        return $this->configuredDefinitions->offsetExists($reflectionClass->name)
            ? []
            : [$reflectionClass->name => new DiDefinitionAutowire($reflectionClass->name)];
    }

    /**
     * @throws DefinitionsLoaderException
     */
    private function getDefinitionsFromFile(string $srcFile): Generator
    {
        if (isset($this->circularLoadFromFileWatcher[$srcFile])) {
            throw new DefinitionsLoaderException(
                sprintf('Detected circular load from the file "%s".', $srcFile)
            );
        }

        try {
            ob_start();
            $content = require $srcFile;
            $this->circularLoadFromFileWatcher[$srcFile] = true;
        } catch (Error|ParseError $e) {
            throw new DefinitionsLoaderException(
                sprintf('Required file has an error: %s. File: "%s".', $e->getMessage(), $srcFile),
                previous: $e
            );
        } finally {
            ob_get_clean();
        }

        if (is_iterable($content)) {
            yield from $content;

            unset($this->circularLoadFromFileWatcher[$srcFile]);

            return;
        }

        if (is_callable($content) && is_iterable($content($this->definitionsConfigurator()))) {
            yield from $content($this->definitionsConfigurator());

            unset($this->circularLoadFromFileWatcher[$srcFile]);

            return;
        }

        if (is_callable($content)) {
            $content($this->definitionsConfigurator());

            yield from [];

            unset($this->circularLoadFromFileWatcher[$srcFile]);

            return;
        }

        throw new DefinitionsLoaderException(
            sprintf('File "%s" return not valid format. File must be use "return" keyword and return any iterable type or callable type.', $srcFile),
        );
    }

    /**
     * @throws DefinitionsLoaderInvalidArgumentException
     */
    private function loadFormFile(bool $overrideDefinitions, string ...$file): void
    {
        foreach ($file as $srcFile) {
            if (!file_exists($srcFile) || !is_readable($srcFile)) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('The file "%s" does not exist or isn\'t readable.', $srcFile)
                );
            }

            try {
                $this->addDefinitions($overrideDefinitions, $this->getDefinitionsFromFile($srcFile));
            } catch (ContainerAlreadyRegisteredException|DefinitionsLoaderExceptionInterface $e) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    message: sprintf('Invalid definition in file "%s".', $srcFile),
                    previous: $e
                );
            }
        }
    }
}
