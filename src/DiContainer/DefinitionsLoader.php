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
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet;
use Kaspi\DiContainer\Exception\ContainerAlreadyRegisteredException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DefinitionsLoaderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\ImportLoaderCollectionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

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
    use DefinitionIdentifierTrait;
    use AttributeReaderTrait;

    private ArrayIterator $configDefinitions;

    /**
     * @var array<non-empty-string, bool>
     */
    private array $mapNamespaceUseAttribute = [];

    public function __construct(private ?ImportLoaderCollectionInterface $importLoaderCollection = null)
    {
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
                $identifier = $this->getIdentifier($identifier, $definition);
            } catch (DiDefinitionExceptionInterface $e) {
                throw new DiDefinitionException(
                    message: sprintf('%s Item position #%d.', $e->getMessage(), $itemCount),
                    previous: $e
                );
            }

            if (!$overrideDefinitions && $this->configDefinitions->offsetExists($identifier)) {
                throw new ContainerAlreadyRegisteredException(
                    sprintf(
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

        if (isset($this->importLoaderCollection)) {
            foreach ($this->importLoaderCollection->getFullyQualifiedName() as ['namespace' => $namespace, 'itemFQN' => $itemFQN]) {
                if ([] !== ($definition = $this->makeDefinitionFromItemFQN($itemFQN, isset($this->mapNamespaceUseAttribute[$namespace])))) {
                    yield from $definition;
                }
            }
        }

        yield from $this->configDefinitions; // @phpstan-ignore generator.keyType
    }

    public function import(string $namespace, string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php'], bool $useAttribute = true): static
    {
        if (null === $this->importLoaderCollection) {
            $this->importLoaderCollection = new ImportLoaderCollection();
        }

        $this->importLoaderCollection->importFromNamespace($namespace, $src, $excludeFilesRegExpPattern, $availableExtensions);

        if ($useAttribute) {
            $this->mapNamespaceUseAttribute[$namespace] = true;
        }

        return $this;
    }

    /**
     * @param ItemFQN $itemFQN
     *
     * @return array<class-string|non-empty-string, DiDefinitionAutowire|DiDefinitionGet>
     *
     * @throws AutowireExceptionInterface
     * @throws DiDefinitionExceptionInterface
     * @throws RuntimeException
     */
    private function makeDefinitionFromItemFQN(array $itemFQN, bool $useAttribute): array
    {
        ['fqn' => $fqn, 'tokenId' => $tokenId] = $itemFQN;

        if (!in_array($tokenId, [T_INTERFACE, T_CLASS], true)) {
            throw new RuntimeException(
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
            throw new RuntimeException(
                message: sprintf('Get fully qualified name "%s" from file "%s:%d" (line #%d). Reason: %s', $fqn, $itemFQN['file'], $itemFQN['line'], $itemFQN['line'], $e->getMessage()),
                previous: $e
            );
        }

        if ($this->isAutowireExclude($reflectionClass)) {
            return $this->configDefinitions->offsetExists($reflectionClass->name)
                ? throw new DiDefinitionException(sprintf('Cannot automatically set definition via #[%s] attribute for container identifier "%s". Configure class "%s" via php attribute or via config file.', AutowireExclude::class, $reflectionClass->name, $reflectionClass->name))
                : [];
        }

        if ($reflectionClass->isInterface()) {
            $service = $this->getServiceAttribute($reflectionClass);

            if (null === $service) {
                return [];
            }

            if ($this->configDefinitions->offsetExists($reflectionClass->name)) {
                throw new DiDefinitionException(
                    sprintf('Cannot automatically set definition via #[%s] attribute for container identifier "%s". Configure class "%s" via php attribute or via config file.', Service::class, $reflectionClass->name, $reflectionClass->name)
                );
            }

            return [$reflectionClass->name => new DiDefinitionGet($service->getIdentifier())];
        }

        if (($autowireAttrs = $this->getAutowireAttribute($reflectionClass))->valid()) {
            $services = [];

            foreach ($autowireAttrs as $autowireAttr) {
                if ($this->configDefinitions->offsetExists($autowireAttr->getIdentifier())) {
                    throw new DiDefinitionException(
                        sprintf('Cannot automatically set definition via #[%s] attribute for container identifier "%s". Configure class "%s" via php attribute or via config file.', Autowire::class, $autowireAttr->getIdentifier(), $reflectionClass->name)
                    );
                }

                $services[$autowireAttr->getIdentifier()] = new DiDefinitionAutowire($reflectionClass->name, $autowireAttr->isSingleton());
            }

            return $services; // @phpstan-ignore return.type
        }

        if (null !== ($diFactoryAttr = $this->getDiFactoryAttribute($reflectionClass))) {
            return [$reflectionClass->name => new DiDefinitionAutowire($diFactoryAttr->getIdentifier(), $diFactoryAttr->isSingleton())];
        }

        return $this->configDefinitions->offsetExists($reflectionClass->name)
            ? []
            : [$reflectionClass->name => new DiDefinitionAutowire($reflectionClass->name)];
    }

    private function getIteratorFromFile(string $srcFile): Generator
    {
        ob_start();
        $content = require $srcFile;
        ob_get_clean();

        return match (true) {
            is_iterable($content) => yield from $content,
            $content instanceof Closure && is_iterable($content()) => yield from $content(),
            default => throw new InvalidArgumentException(
                sprintf('File "%s" return not valid format. File must be use "return" keyword, and return any iterable type or callback function using "yield" keyword.', $srcFile)
            )
        };
    }

    private function loadFormFile(bool $overrideDefinitions, string ...$file): void
    {
        foreach ($file as $srcFile) {
            if (!file_exists($srcFile) || !is_readable($srcFile)) {
                throw new InvalidArgumentException(sprintf('The file "%s" does not exist or is not readable.', $srcFile));
            }

            try {
                $this->addDefinitions($overrideDefinitions, $this->getIteratorFromFile($srcFile));
                unset($srcFile);
            } catch (ContainerExceptionInterface|DiDefinitionExceptionInterface $e) {
                throw new ContainerException(
                    sprintf('Invalid definition in file "%s". Reason: %s', $srcFile, $e->getMessage())
                );
            }
        }
    }
}
