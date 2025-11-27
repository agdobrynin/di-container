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
use Kaspi\DiContainer\Attributes\Service;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionFactory;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Exception\AutowireParameterTypeException;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\DefinitionsLoaderException;
use Kaspi\DiContainer\Exception\DefinitionsLoaderInvalidArgumentException;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\ImportLoaderCollectionInterface;
use ParseError;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use SplFileInfo;
use SplFileObject;

use function class_exists;
use function file_exists;
use function in_array;
use function is_iterable;
use function is_readable;
use function ob_get_clean;
use function ob_start;
use function sprintf;
use function str_replace;
use function unlink;
use function var_export;

use const T_CLASS;
use const T_INTERFACE;

/**
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class DefinitionsLoader implements DefinitionsLoaderInterface
{
    private ArrayIterator $configDefinitions;

    /**
     * @var array<non-empty-string, bool>
     */
    private array $mapNamespaceUseAttribute = [];

    private SplFileInfo $splFileInfoImportCacheFile;

    public function __construct(
        private readonly ?string $importCacheFile = null,
        private ?ImportLoaderCollectionInterface $importLoaderCollection = null,
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

    public function import(string $namespace, string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php'], bool $useAttribute = true): static
    {
        if (null !== ($file = $this->getImportCacheFile()) && $file->isFile()) {
            return $file->isReadable()
                ? $this
                : throw new DefinitionsLoaderInvalidArgumentException(
                    sprintf('The cache file for importing definitions is not readable. File: "%s".', $file->getPathname())
                );
        }

        if (null === $this->importLoaderCollection) {
            $this->importLoaderCollection = new ImportLoaderCollection();
        }

        try {
            $this->importLoaderCollection->importFromNamespace($namespace, $src, $excludeFilesRegExpPattern, $availableExtensions);
        } catch (InvalidArgumentException|RuntimeException $e) {
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

        /**
         * @var null|SplFileObject $importCacheFile
         */
        $importCacheFile = $this->getImportCacheFile();

        if (null !== $importCacheFile && $importCacheFile->isFile()) {
            if (!$importCacheFile->isReadable()) {
                throw (
                    new DefinitionsLoaderException(
                        message: sprintf('The cache file for importing definitions is not readable. File: "%s".', $importCacheFile->getPathname()),
                    )
                )
                    ->setContext(context_file: $importCacheFile)
                ;
            }

            yield from $this->getIteratorFromFile($importCacheFile->getPathname()); // @phpstan-ignore generator.keyType
        }

        if (isset($this->importLoaderCollection)) {
            $cacheFileDelete = static function (?SplFileObject $f) {
                if (null !== $f) {
                    @unlink($f->getPathname());
                }
            };

            try {
                $cacheFileOpened = $importCacheFile?->openFile('wb+');
                $cacheFileOpened?->fwrite(
                    '<?php'.PHP_EOL
                    .'use function Kaspi\DiContainer\{diAutowire, diFactory, diGet};'.PHP_EOL
                    .'return static function () {'.PHP_EOL
                );
            } catch (RuntimeException $e) {
                throw new DefinitionsLoaderException(
                    sprintf('The cache file for importing definitions is not writable. File: "%s".', $importCacheFile->getPathname()),
                    previous: $e
                );
            }

            foreach ($this->importLoaderCollection->getImportLoaders() as $namespace => $importLoader) {
                /** @var Generator<non-negative-int, ItemFQN> $fullyQualifiedName */
                $fullyQualifiedName = $importLoader->getFullyQualifiedName($namespace);

                do {
                    try {
                        /** @var ItemFQN $itemFQN */
                        $itemFQN = $fullyQualifiedName->current();
                    } catch (InvalidArgumentException|RuntimeException $e) {
                        $cacheFileDelete($cacheFileOpened);

                        throw new DefinitionsLoaderException(
                            sprintf('Cannot get fully qualified name for php class or interface from source directory "%s" with namespace "%s". Reason: %s', $importLoader->getSrc(), $namespace, $e->getMessage()),
                            previous: $e
                        );
                    }

                    try {
                        $definition = $this->makeDefinitionFromItemFQN($itemFQN, isset($this->mapNamespaceUseAttribute[$namespace]));
                    } catch (AutowireAttributeException|AutowireParameterTypeException|DefinitionsLoaderInvalidArgumentException $e) {
                        $cacheFileDelete($cacheFileOpened);

                        throw new DefinitionsLoaderException(
                            sprintf('Cannot make container definition from source directory "%s" with namespace "%s". The fully qualified name "%s" in file %s:%d. Reason: %s', $importLoader->getSrc(), $namespace, $itemFQN['fqn'], $itemFQN['file'], $itemFQN['line'] ?? 0, $e->getMessage()),
                            previous: $e
                        );
                    }

                    if ([] !== $definition) {
                        foreach ($definition as $identifier => $definitionItem) {
                            $cacheFileOpened?->fwrite($this->generateYieldStringDefinition($identifier, $definitionItem).PHP_EOL);

                            yield $identifier => $definitionItem;
                        }
                    }

                    $fullyQualifiedName->next();
                } while ($fullyQualifiedName->valid());
            }

            $cacheFileOpened?->fwrite('};'.PHP_EOL);
        }

        yield from $this->configDefinitions; // @phpstan-ignore generator.keyType
    }

    private function generateYieldStringDefinition(string $identifier, DiDefinitionAutowire|DiDefinitionFactory|DiDefinitionGet $definition): string
    {
        $identifier = str_replace('"', '\"', $identifier);

        if ($definition instanceof DiDefinitionAutowire) {
            return sprintf(
                '    yield "%s" => diAutowire("%s", %s);',
                $identifier,
                str_replace('"', '\"', $definition->getIdentifier()),
                var_export($definition->isSingleton(), true)
            );
        }

        if ($definition instanceof DiDefinitionFactory) {
            return sprintf(
                '    yield "%s" => diFactory("%s", %s);',
                $identifier,
                str_replace('"', '\"', $definition->getIdentifier()),
                var_export($definition->isSingleton(), true)
            );
        }

        return sprintf(
            '    yield "%s" => diGet("%s");',
            $identifier,
            str_replace('"', '\"', $definition->getDefinition())
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
                    sprintf('The fully qualified name "%s" mark as excluded via php attribute "%s". This fully qualified name "%s" must be configure via php attribute or via config file.', $reflectionClass->name, AutowireExclude::class, $reflectionClass->name)
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
                    sprintf('Cannot automatically set definition via php attribute "%s". Container identifier "%s" already registered. This interface "%s" must be configure via php attribute or via config file.', Service::class, $reflectionClass->name, $reflectionClass->name)
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
                if ($this->configDefinitions->offsetExists($autowireAttr->getIdentifier())) {
                    throw new DefinitionsLoaderInvalidArgumentException(
                        sprintf('Cannot automatically set definition via php attribute "%s". Container identifier "%s" already registered. This fully qualified name "%s" must be configure via php attribute or via config file.', Autowire::class, $autowireAttr->getIdentifier(), $reflectionClass->name)
                    );
                }

                $services[$autowireAttr->getIdentifier()] = new DiDefinitionAutowire($reflectionClass->name, $autowireAttr->isSingleton());
            }

            return $services; // @phpstan-ignore return.type
        }

        if (null !== ($factory = AttributeReader::getDiFactoryAttribute($reflectionClass))) {
            return [$reflectionClass->name => new DiDefinitionFactory($factory->getIdentifier(), $factory->isSingleton())];
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
            default => throw (
                new DefinitionsLoaderException(
                    message: sprintf('File "%s" return not valid format. File must be use "return" keyword, and return any iterable type or callback function using "yield" keyword.', $srcFile),
                )
            )
                ->setContext(context_content: $content)
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
                    sprintf('The file "%s" does not exist or is not readable.', $srcFile)
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
