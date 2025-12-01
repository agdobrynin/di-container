<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use SplPriorityQueue;

use function array_map;
use function explode;
use function get_debug_type;
use function in_array;
use function interface_exists;
use function is_callable;
use function is_string;
use function sprintf;
use function str_starts_with;
use function trim;
use function var_export;

final class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    private bool $tagIsInterface;
    private bool $keyChecked;
    private bool $isUseKeysComputed;
    private DiContainerInterface $container;

    private ?DiDefinitionAutowireInterface $callingByDefinitionAutowire = null;

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
        if (null === $context || $context instanceof DiDefinitionAutowireInterface) {
            $this->callingByDefinitionAutowire = $context;
        }

        $this->container = $container;

        return $this->isLazy
            ? new LazyDefinitionIterator($container, $this->getContainerIdentifiers())
            : array_map(fn (string $id) => $container->get($id), $this->getContainerIdentifiers());
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    /**
     * @return array<non-empty-string, non-empty-string>|list<non-empty-string>
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function getContainerIdentifiers(): array
    {
        return $this->isUseKeysComputed
            ? $this->getContainerIdentifiersWithKey()
            : $this->getContainerIdentifiersWithoutKey();
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function getContainerIdentifiersWithKey(): array
    {
        $mapKeyCollectionToContainerIdentifier = [];

        foreach ($this->getContainerIdentifiersOfTaggedServiceByTag() as [$containerIdentifier, $definition]) {
            $keyCollection = $this->getTagKeyFromTagOptionsOrFromClassMethod($containerIdentifier, $definition);

            if (!isset($mapKeyCollectionToContainerIdentifier[$keyCollection])) {
                $mapKeyCollectionToContainerIdentifier[$keyCollection] = $containerIdentifier;
            }
        }

        return $mapKeyCollectionToContainerIdentifier;
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function getContainerIdentifiersWithoutKey(): array
    {
        $containerIdentifiers = [];

        foreach ($this->getContainerIdentifiersOfTaggedServiceByTag() as [$containerIdentifier]) {
            $containerIdentifiers[] = $containerIdentifier;
        }

        return $containerIdentifiers;
    }

    /**
     * @return Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}>
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function getContainerIdentifiersOfTaggedServiceByTag(): Generator
    {
        $this->tagIsInterface ??= interface_exists($this->tag);
        $taggedServices = new SplPriorityQueue();
        $taggedServices->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        /** @var non-empty-string $containerIdentifier */
        foreach ($this->container->getDefinitions() as $containerIdentifier => $definition) {
            if (false === ($definition instanceof DiTaggedDefinitionInterface)
                || in_array($containerIdentifier, $this->containerIdExclude, true)
                || ($this->selfExclude && $containerIdentifier === $this->callingByDefinitionAutowire?->getDefinition()->getName())) {
                continue;
            }

            $tagImplementInterface = false;

            if ($definition instanceof DiDefinitionAutowireInterface) {
                $definition->setContainer($this->container);
                $tagImplementInterface = $this->tagIsInterface
                    && $definition->getDefinition()->implementsInterface($this->tag);
            }

            if ((!$this->tagIsInterface && $definition->hasTag($this->tag)) || $tagImplementInterface) {
                $operationOptions = [];

                if ($definition instanceof DiDefinitionAutowireInterface) {
                    $operationOptions['priority.default_method'] = $this->priorityDefaultMethod;
                }

                // 🚩 Tag with higher priority early in list.
                $taggedServices->insert([$containerIdentifier, $definition], $definition->geTagPriority($this->tag, $operationOptions));
            }
        }

        /** @var array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface} $item */
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
    private function getTagKeyFromTagOptionsOrFromClassMethod(string $identifier, DiDefinitionAutowireInterface|DiTaggedDefinitionInterface $taggedAs): string
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

                if (!$taggedAs instanceof DiDefinitionAutowireInterface) {
                    return $optionKey;
                }

                if (!str_starts_with($optionKey, 'self::')) {
                    return $optionKey;
                }

                try {
                    $method = explode('::', $optionKey)[1];

                    return $this->getTagKeyFromClassMethod($taggedAs->getIdentifier(), $method, $taggedAs);
                } catch (AutowireException|InvalidArgumentException $e) {
                    throw new DiDefinitionException(
                        message: sprintf('Cannot get key for tag "%s" via method %s::%s().', $this->tag, $taggedAs->getIdentifier(), $method),
                        previous: $e
                    );
                }
            }
        }

        if (null === $this->keyDefaultMethod || !($taggedAs instanceof DiDefinitionAutowireInterface)) {
            return $identifier;
        }

        try {
            return $this->getTagKeyFromClassMethod($taggedAs->getIdentifier(), $this->keyDefaultMethod, $taggedAs);
        } catch (InvalidArgumentException) {
            return $identifier;
        } catch (AutowireException $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get key for tag "%s" via default method %s::%s().', $this->tag, $taggedAs->getIdentifier(), $this->keyDefaultMethod),
                previous: $e
            );
        }
    }

    /**
     * @return non-empty-string
     *
     * @throws AutowireException|InvalidArgumentException
     */
    private function getTagKeyFromClassMethod(string $class, string $method, DiTaggedDefinitionInterface $taggedAs): string
    {
        $callable = [$class, $method];

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Method must be declared with public and static modifiers.');
        }

        $key = $callable($this->tag, $taggedAs->getTag($this->tag) ?? []);

        if (!is_string($key)) {
            throw new AutowireException(sprintf('Method must return type "string" but return type is "%s"', get_debug_type($key)));
        }

        return '' === $key || '' === trim($key)
            ? throw new AutowireException('Return value must be non-empty string.')
            : $key;
    }
}
