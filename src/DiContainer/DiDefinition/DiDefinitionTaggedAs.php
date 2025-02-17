<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\DiDefinitionAutowireTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    use DiContainerTrait;
    use DiDefinitionAutowireTrait;

    private bool $tagIsInterface;
    private string $keyOptimized;

    /**
     * @param non-empty-string      $tag
     * @param null|non-empty-string $priorityDefaultMethod priority from class::method()
     * @param null|non-empty-string $key                   identifier of tagged definition from tag options (meta-data)
     * @param null|non-empty-string $keyDefaultMethod      if $key not found in tag options - try get it from class::method()
     */
    public function __construct(
        private string $tag,
        private bool $isLazy = true,
        private ?string $priorityDefaultMethod = null,
        private bool $useKeys = true,
        private ?string $key = null,
        private ?string $keyDefaultMethod = null,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServicesTaggedAs(): iterable
    {
        /**
         * Key as container identifier, value as container definition.
         *
         * @var \Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}> $items
         */
        $items = $this->getContainerIdentifiersOfTaggedServiceByTag();

        if (!$items->valid()) {
            // @phpstan-ignore return.type
            return $this->isLazy
                ? new LazyDefinitionIterator($this->getContainer(), [])
                : [];
        }

        $isUseKeys = $this->useKeys || null !== $this->key || null !== $this->keyDefaultMethod;

        if (!$this->isLazy) {
            // @phpstan-var array<non-empty-string, mixed> $services
            $services = [];

            foreach ($items as [$containerIdentifier, $item]) {
                if ($isUseKeys) {
                    $keyCollection = $this->getKeyFromTagOptionsOrFromKeyDefaultMethod($containerIdentifier, $item);

                    if (!isset($services[$keyCollection])) {
                        $services[$keyCollection] = $this->getContainer()->get($containerIdentifier);
                    }
                } else {
                    $services[] = $this->getContainer()->get($containerIdentifier);
                }
            }

            return $services;
        }

        // @phpstan-var array<non-empty-string, none-empty-string> $services
        $mapKeyCollectionToContainerIdentifier = [];

        foreach ($items as [$containerIdentifier, $item]) {
            if ($isUseKeys) {
                $keyCollection = $this->getKeyFromTagOptionsOrFromKeyDefaultMethod($containerIdentifier, $item);

                if (!isset($mapKeyCollectionToContainerIdentifier[$keyCollection])) {
                    $mapKeyCollectionToContainerIdentifier[$keyCollection] = $containerIdentifier;
                }
            } else {
                $mapKeyCollectionToContainerIdentifier[] = $containerIdentifier;
            }
        }

        return new LazyDefinitionIterator($this->getContainer(), $mapKeyCollectionToContainerIdentifier); // @phpstan-ignore return.type
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    /**
     * @return \Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}>
     *
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getContainerIdentifiersOfTaggedServiceByTag(): \Generator
    {
        $this->tagIsInterface ??= \interface_exists($this->tag);
        $taggedServices = new \SplPriorityQueue();
        $taggedServices->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

        /** @var non-empty-string $containerIdentifier */
        foreach ($this->getContainer()->getDefinitions() as $containerIdentifier => $definition) {
            if (false === ($definition instanceof DiTaggedDefinitionInterface)) {
                continue;
            }

            if ($definition instanceof DiDefinitionInvokableInterface) {
                $definition->setContainer($this->getContainer());
            }

            if (
                (
                    !$this->tagIsInterface && $definition->hasTag($this->tag)
                )
                || (
                    $this->tagIsInterface
                        && $definition instanceof DiDefinitionAutowireInterface
                        && $definition->getDefinition()->implementsInterface($this->tag)
                )
            ) {
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
     */
    private function getKeyFromTagOptionsOrFromKeyDefaultMethod(string $identifier, DiDefinitionAutowireInterface|DiTaggedDefinitionInterface $taggedAs): string
    {
        if (null !== $this->key) {
            $this->keyOptimized ??= '' !== \trim($this->key)
                ? $this->key
                : throw new AutowireException('Argument $key must be non-empty string.');

            $optionKey = $taggedAs->getTag($this->tag)[$this->keyOptimized] ?? null;

            if (null !== $optionKey) {
                if (!\is_string($optionKey) || '' === \trim($optionKey)) {
                    throw new AutowireException(
                        \sprintf(
                            'Tag option "%s" for container identifier "%s" with tag "%s" has an error: the value must be non-empty string. Got: "%s".',
                            $this->keyOptimized,
                            $identifier,
                            $this->tag,
                            \var_export($optionKey, true)
                        )
                    );
                }

                if ($taggedAs instanceof DiDefinitionAutowireInterface && \str_starts_with($optionKey, 'self::')) {
                    $method = \explode('::', $optionKey)[1];
                    $howGetOptions = \sprintf('Get key by "%s::%s()" for tag "%s" has an error:', $taggedAs->getDefinition()->name, $method, $this->tag);

                    // @phpstan-ignore return.type
                    return self::callStaticMethod($taggedAs, $method, true, $howGetOptions, ['string'], $this->tag, $taggedAs->getTag($this->tag) ?? []);
                }

                return $optionKey; // @phpstan-ignore return.type
            }
        }

        if (null === $this->keyDefaultMethod || !($taggedAs instanceof DiDefinitionAutowireInterface)) {
            return $identifier;
        }

        $howGetKeyOption = \sprintf('Get default key by "%s::%s()" for tag "%s" has an error:', $taggedAs->getDefinition()->name, $this->keyDefaultMethod, $this->tag);
        $key = self::callStaticMethod($taggedAs, $this->keyDefaultMethod, false, $howGetKeyOption, ['string'], $this->tag, $taggedAs->getTag($this->tag) ?? []);

        // @phpstan-ignore return.type
        return null === $key || '' === $key
            ? $identifier
            : $key;
    }
}
