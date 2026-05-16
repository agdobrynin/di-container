<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedObjectDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use SplPriorityQueue;

use function array_flip;
use function array_map;
use function explode;
use function is_callable;
use function is_string;
use function sprintf;
use function str_starts_with;
use function trim;
use function var_export;

final class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    private bool $keyChecked;
    private readonly bool $isUseKeysComputed;

    /**
     * @var array<non-empty-string, int>
     */
    private array $flippedContainerIdExclude;

    private ?DiTaggedObjectDefinitionInterface $callingByDefinition = null;

    /**
     * @param non-empty-string       $tag
     * @param null|non-empty-string  $priorityDefaultMethod priority from class::method()
     * @param null|non-empty-string  $key                   identifier of tagged definition from tag options (meta-data)
     * @param null|non-empty-string  $keyDefaultMethod      if $key not found in tag options - try get it from class::method()
     * @param list<non-empty-string> $containerIdExclude    exclude container identifiers from collection
     * @param bool                   $selfExclude           exclude the php calling class from the collection
     */
    public function __construct(
        private readonly string $tag,
        private readonly bool $isLazy = true,
        private readonly ?string $priorityDefaultMethod = null,
        bool $useKeys = true,
        private readonly ?string $key = null,
        private readonly ?string $keyDefaultMethod = null,
        private readonly array $containerIdExclude = [],
        private readonly bool $selfExclude = true,
    ) {
        $this->isUseKeysComputed = $useKeys || null !== $key || null !== $keyDefaultMethod;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): iterable
    {
        return $this->isLazy
            ? new LazyDefinitionIterator($container, $this->exposeContainerIdentifiers($container, $context))
            : array_map($container->get(...), $this->exposeContainerIdentifiers($container, $context));
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    /**
     * @return array<non-empty-string|non-negative-int, non-empty-string>
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeContainerIdentifiers(DiContainerInterface $container, mixed $context = null): iterable
    {
        if ($context instanceof DiTaggedObjectDefinitionInterface) {
            $this->callingByDefinition = $context;
        }

        $mapKeyToContainerIdentifier = [];

        foreach ($this->filterByExcludeSortDefinitionByPriority($container->findTaggedDefinitions($this->tag)) as [$containerIdentifier, $definition]) {
            if ($this->isUseKeysComputed) {
                $keyCollection = $this->getTagKeyFromTagOptionsOrFromClassMethod($containerIdentifier, $definition);
                if (!isset($mapKeyToContainerIdentifier[$keyCollection])) {
                    $mapKeyToContainerIdentifier[$keyCollection] = $containerIdentifier;
                }
            } else {
                $mapKeyToContainerIdentifier[] = $containerIdentifier;
            }
        }

        return $mapKeyToContainerIdentifier;
    }

    /**
     * @param iterable<non-empty-string, DiTaggedDefinitionInterface|DiTaggedObjectDefinitionInterface> $definitions
     *
     * @return Generator<array{0: non-empty-string, 1: DiTaggedDefinitionInterface|DiTaggedObjectDefinitionInterface}>
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function filterByExcludeSortDefinitionByPriority(iterable $definitions): Generator
    {
        if ($definitions instanceof Generator && !$definitions->valid()) {
            return;
        }

        $taggedServices = new SplPriorityQueue();
        $taggedServices->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        $this->flippedContainerIdExclude ??= array_flip($this->containerIdExclude);

        foreach ($definitions as $containerIdentifier => $definition) {
            if (isset($this->flippedContainerIdExclude[$containerIdentifier])
                || ($this->selfExclude && $containerIdentifier === $this->callingByDefinition?->getDefinitionIdentifier())) {
                continue;
            }

            $operationOptions = [];

            if ($definition instanceof DiTaggedObjectDefinitionInterface) {
                $operationOptions['priority.default_method'] = $this->priorityDefaultMethod;
            }

            // 🚩 Tag with higher priority early in list.
            $taggedServices->insert([$containerIdentifier, $definition], $definition->geTagPriority($this->tag, $operationOptions));
        }

        /** @var array{0: non-empty-string, 1: DiTaggedDefinitionInterface|DiTaggedObjectDefinitionInterface} $item */
        foreach ($taggedServices as $item) {
            yield $item;
        }
    }

    /**
     * @param non-empty-string $identifier
     *
     * @return non-empty-string
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function getTagKeyFromTagOptionsOrFromClassMethod(string $identifier, DiTaggedDefinitionInterface|DiTaggedObjectDefinitionInterface $taggedAs): string
    {
        if (null !== $this->key) {
            if (!isset($this->keyChecked)) {
                if ('' === trim($this->key)) {
                    throw new DiDefinitionException(
                        sprintf('Parameter $key for %s::__construct() must be non-empty string. Tag is "%s".', self::class, $this->tag)
                    );
                }

                $this->keyChecked = true;
            }

            $optionKey = $taggedAs->getTag($this->tag)[$this->key] ?? null;

            if (null !== $optionKey) {
                if (!is_string($optionKey) || '' === trim($optionKey)) {
                    throw new DiDefinitionException(
                        sprintf('Cannot get key for tag "%s" via tag options. The value of option name "%s" must be non-empty string. Got value: %s', $this->tag, $this->key, var_export($optionKey, true))
                    );
                }

                if (!$taggedAs instanceof DiTaggedObjectDefinitionInterface) {
                    return $optionKey;
                }

                if (!str_starts_with($optionKey, 'self::')) {
                    return $optionKey;
                }

                try {
                    $method = explode('::', $optionKey)[1];

                    return $this->getTagKeyFromClassMethod($taggedAs->getDefinitionIdentifier(), $method, $taggedAs);
                } catch (AutowireException|InvalidArgumentException $e) {
                    throw new DiDefinitionException(
                        message: sprintf('Cannot get key for tag "%s" via method %s::%s(). Caused by: %s', $this->tag, $taggedAs->getDefinitionIdentifier(), $method, $e->getMessage()),
                        previous: $e
                    );
                }
            }
        }

        if (null === $this->keyDefaultMethod || !($taggedAs instanceof DiTaggedObjectDefinitionInterface)) {
            return $identifier;
        }

        try {
            return $this->getTagKeyFromClassMethod($taggedAs->getDefinitionIdentifier(), $this->keyDefaultMethod, $taggedAs);
        } catch (InvalidArgumentException) {
            return $identifier;
        } catch (AutowireException $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get key for tag "%s" via default method %s::%s(). Caused by: %s', $this->tag, $taggedAs->getDefinitionIdentifier(), $this->keyDefaultMethod, $e->getMessage()),
                previous: $e
            );
        }
    }

    /**
     * @return non-empty-string
     *
     * @throws AutowireException|InvalidArgumentException
     */
    private function getTagKeyFromClassMethod(string $class, string $method, DiTaggedObjectDefinitionInterface $taggedAs): string
    {
        $callable = [$class, $method];

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Method must be exist and declared with public and static modifiers.');
        }

        $key = $callable($this->tag, $taggedAs->getTag($this->tag) ?? []);

        return is_string($key) && '' !== $key
            ? $key
            : throw new AutowireException(sprintf('Method must return non-empty string but return "%s"', var_export($key, true)));
    }
}
