<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use ArrayIterator;
use Closure;
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
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\FinderFullyQualifiedNameCollectionInterface;
use ParseError;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use SplFileInfo;

use function class_exists;
use function file_exists;
use function in_array;
use function is_iterable;
use function is_readable;
use function ob_get_clean;
use function ob_start;
use function sprintf;
use function var_export;

use const T_CLASS;
use const T_INTERFACE;

/**
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class DefinitionsLoader implements DefinitionsLoaderInterface
{
    /** @var ArrayIterator<non-empty-string, mixed> */
    private ArrayIterator $configDefinitions;

    /**
     * @var array<non-empty-string, bool>
     */
    private array $mapNamespaceUseAttribute = [];

    private SplFileInfo $splFileInfoImportCacheFile;

    public function __construct(
        private readonly ?string $importCacheFile = null,
        private ?FinderFullyQualifiedNameCollectionInterface $finderFullyQualifiedNameCollection = null,
    ) {
        $this->configDefinitions = new ArrayIterator();
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

            if (!$overrideDefinitions && $this->configDefinitions->offsetExists($identifier)) {
                throw new ContainerAlreadyRegisteredException(
                    sprintf(
                        'Definition with identifier "%s" is already registered in container. Item position #%d.',
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

    /**
     * Note: parameter `$excludeFiles` use php function `\fnmatch()`, detail info about file pattern see in documentation.
     *
     * @see https://www.php.net/manual/en/function.fnmatch.php
     */
    public function import(string $namespace, string $src, array $excludeFiles = [], array $availableExtensions = ['php'], bool $useAttribute = true): static
    {
        if (null !== ($file = $this->getImportCacheFile()) && $file->isFile()) {
            return $file->isReadable()
                ? $this
                : throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('The cache file for importing definitions isn\'t readable. File: "%s".', $file->getPathname())
                );
        }

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

        if ($useAttribute) {
            $this->mapNamespaceUseAttribute[$namespace] = true;
        }

        return $this;
    }

    public function definitions(): iterable
    {
        $this->configDefinitions->rewind();
        $importCacheFile = $this->getImportCacheFile();

        if (null !== $importCacheFile && $importCacheFile->isFile()) {
            if (!$importCacheFile->isReadable()) {
                throw new DefinitionsLoaderException(
                    sprintf('The cache file for importing definitions isn\'t readable. File: "%s".', $importCacheFile->getPathname()),
                );
            }

            yield from $this->getIteratorFromFile($importCacheFile->getPathname()); // @phpstan-ignore generator.keyType
        }

        if ($this->importedDefinitions()->valid()) {
            $importedDefinitions = $this->importedDefinitions();
            $importedDefinitions->rewind();

            try {
                $cacheFileOpened = $importCacheFile?->openFile('wb+');
                $cacheFileOpened?->fwrite(
                    '<?php'.PHP_EOL
                    .'use function Kaspi\DiContainer\{diAutowire, diFactory, diGet};'.PHP_EOL
                    .'return static function (): \Generator {'.PHP_EOL
                );
            } catch (RuntimeException $e) {
                throw new DefinitionsLoaderException(
                    sprintf('The cache file for importing definitions isn\'t writable. File: "%s".', $importCacheFile->getPathname()),
                    previous: $e
                );
            }

            do {
                $identifier = $importedDefinitions->key();
                $definition = $importedDefinitions->current();
                $cacheFileOpened?->fwrite($this->generateYieldStringDefinition($identifier, $definition).PHP_EOL);

                yield $identifier => $definition;

                $importedDefinitions->next();
            } while ($importedDefinitions->valid());

            $cacheFileOpened?->fwrite('};'.PHP_EOL);
        }

        yield from $this->configDefinitions;
    }

    public function reset(): void
    {
        $this->configDefinitions = new ArrayIterator();
        $this->mapNamespaceUseAttribute = [];
        $this->finderFullyQualifiedNameCollection?->reset();
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

        /** @var array<non-empty-string, DiDefinitionAutowire|DiDefinitionFactory|DiDefinitionGet> $importedDefinitions */
        $importedDefinitions = [];

        foreach ($this->finderFullyQualifiedNameCollection->get() as $finderFQN) {
            $fullQualifiedName = $finderFQN->get();

            do {
                try {
                    /** @var null|ItemFQN $itemFQN */
                    $itemFQN = $fullQualifiedName->current();

                    if (null === $itemFQN) {
                        break;
                    }
                } catch (InvalidArgumentException|RuntimeException $e) {
                    throw new DefinitionsLoaderException(
                        sprintf('Cannot get fully qualified name for php class or interface from source directory "%s" with namespace "%s". Reason: %s', $finderFQN->getSrc(), $finderFQN->getNamespace(), $e->getMessage()),
                        previous: $e
                    );
                }

                try {
                    $definitions = $this->makeDefinitionFromItemFQN($itemFQN, isset($this->mapNamespaceUseAttribute[$finderFQN->getNamespace()]));
                } catch (AutowireAttributeException|AutowireParameterTypeException|DefinitionsLoaderInvalidArgumentException $e) {
                    throw new DefinitionsLoaderException(
                        sprintf('Cannot make container definition from source directory "%s" with namespace "%s". The fully qualified name "%s" in file %s:%d. Reason: %s', $finderFQN->getSrc(), $finderFQN->getNamespace(), $itemFQN['fqn'], $itemFQN['file'], $itemFQN['line'] ?? 0, $e->getMessage()),
                        previous: $e
                    );
                }

                foreach ($definitions as $identifier => $definition) {
                    if ($definition instanceof DiDefinitionAutowire && isset($importedDefinitions[$identifier]) && $importedDefinitions[$identifier] instanceof DiDefinitionAutowire) {
                        throw new DefinitionsLoaderException(
                            sprintf('Container identifier "%s" already import for class "%s". Please specify container identifier for class "%s".', $identifier, $importedDefinitions[$identifier]->getIdentifier(), $definition->getIdentifier())
                        );
                    }

                    $importedDefinitions[$identifier] = $definition;
                }

                $fullQualifiedName->next();
            } while ($fullQualifiedName->valid());
        }

        /*
         * Check valid DiDefinitionGet for imported definitions.
         * Interface maybe configured via php attribute #[Service('container_id')].
        */
        foreach ($importedDefinitions as $identifier => $definition) {
            if (!$definition instanceof DiDefinitionGet) {
                continue;
            }

            $containerIdentifier = $definition->getDefinition();

            if (!isset($importedDefinitions[$containerIdentifier])
                && !$this->configDefinitions->offsetExists($containerIdentifier)) {
                throw new DefinitionsLoaderException(
                    sprintf('The container identifier "%s" is not registered. The reference from the definition with the id "%s".', $containerIdentifier, $identifier)
                );
            }
        }

        yield from $importedDefinitions;
    }

    private function generateYieldStringDefinition(string $identifier, DiDefinitionAutowire|DiDefinitionFactory|DiDefinitionGet $definition): string
    {
        if ($definition instanceof DiDefinitionAutowire) {
            return sprintf(
                '    yield %s => diAutowire(%s, %s);',
                var_export($identifier, true),
                var_export($definition->getIdentifier(), true),
                var_export($definition->isSingleton(), true),
            );
        }

        if ($definition instanceof DiDefinitionFactory) {
            return sprintf(
                '    yield %s => diFactory(%s, %s);',
                var_export($identifier, true),
                var_export($definition->getDefinition(), true),
                var_export($definition->isSingleton(), true),
            );
        }

        return sprintf(
            '    yield %s => diGet("%s");',
            var_export($identifier, true),
            var_export($definition->getDefinition(), true),
        );
    }

    /**
     * @param ItemFQN $itemFQN
     *
     * @return array<class-string|non-empty-string, DiDefinitionAutowire|DiDefinitionFactory|DiDefinitionGet>
     *
     * @throws AutowireAttributeException|AutowireParameterTypeException|DefinitionsLoaderInvalidArgumentException
     */
    private function makeDefinitionFromItemFQN(array $itemFQN, bool $useAttribute): array
    {
        ['fqn' => $fqn, 'tokenId' => $tokenId] = $itemFQN;

        if (!in_array($tokenId, [T_INTERFACE, T_CLASS], true)) {
            throw new DefinitionsLoaderInvalidArgumentException(
                sprintf('Unsupported token id. Support only T_INTERFACE with id %d, T_CLASS with id %d. Got %s.', T_INTERFACE, T_CLASS, var_export($tokenId, true))
            );
        }

        if (!$useAttribute) {
            return $this->configDefinitions->offsetExists($fqn) || T_INTERFACE === $tokenId
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
            if ($this->configDefinitions->offsetExists($reflectionClass->name)) {
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

            if ($this->configDefinitions->offsetExists($reflectionClass->name)) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('Cannot automatically configure interface "%s" via php attribute "%s". Container identifier "%s" already registered. This interface "%s" must be configure via php attribute or via config file.', $reflectionClass->name, Service::class, $reflectionClass->name, $reflectionClass->name)
                );
            }

            return [
                $reflectionClass->name => class_exists($service->getIdentifier())
                    ? new DiDefinitionAutowire($service->getIdentifier(), $service->isSingleton())
                    : new DiDefinitionGet($service->getIdentifier()),
            ];
        }

        if (($autowireAttrs = AttributeReader::getAutowireAttribute($reflectionClass))->valid()) {
            $services = [];

            foreach ($autowireAttrs as $autowireAttr) {
                if ($this->configDefinitions->offsetExists($autowireAttr->id)) { // @phpstan-ignore argument.type
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
            if ($this->configDefinitions->offsetExists($reflectionClass->name)) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('Cannot automatically configure class "%s" via php attribute "%s". The class "%s" already registered. This class "%s" must be configure via php attribute or via config file.', $reflectionClass->name, DiFactory::class, $reflectionClass->name, $reflectionClass->name)
                );
            }

            $diFactory = new DiDefinitionFactory($factory->definition, $factory->isSingleton);

            return [$reflectionClass->name => $diFactory->bindArguments(...$factory->arguments)];
        }

        return $this->configDefinitions->offsetExists($reflectionClass->name)
            ? []
            : [$reflectionClass->name => new DiDefinitionAutowire($reflectionClass->name)];
    }

    /**
     * @throws DefinitionsLoaderException
     */
    private function getIteratorFromFile(string $srcFile): Generator
    {
        try {
            ob_start();
            $content = require $srcFile;
        } catch (Error|ParseError $e) {
            throw new DefinitionsLoaderException(
                sprintf('Required file has an error: %s. File: "%s".', $e->getMessage(), $srcFile),
                previous: $e
            );
        } finally {
            ob_get_clean();
        }

        return match (true) {
            is_iterable($content) => yield from $content,
            $content instanceof Closure && is_iterable($content()) => yield from $content(),
            default => throw new DefinitionsLoaderException(
                sprintf('File "%s" return not valid format. File must be use "return" keyword, and return any iterable type or callback function using "yield" keyword.', $srcFile),
            )
        };
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
                $this->addDefinitions($overrideDefinitions, $this->getIteratorFromFile($srcFile));
                unset($srcFile);
            } catch (ContainerAlreadyRegisteredException|DefinitionsLoaderExceptionInterface $e) {
                throw new DefinitionsLoaderInvalidArgumentException(
                    message: sprintf('Invalid definition in file "%s".', $srcFile),
                    previous: $e
                );
            }
        }
    }

    private function getImportCacheFile(): ?SplFileInfo
    {
        return null !== $this->importCacheFile
            ? $this->splFileInfoImportCacheFile ??= new SplFileInfo($this->importCacheFile)
            : null;
    }
}
