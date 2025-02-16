<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
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
     * @param null|non-empty-string $key                   identifier of definition from meta-data
     * @param null|non-empty-string $keyDefaultMethod      if $keyFromOptions not found try get it from class::method()
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
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServicesTaggedAs(): iterable
    {
        $this->tagIsInterface ??= \interface_exists($this->tag);

        /**
         * @var \Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}> $items
         */
        $items = $this->tagIsInterface
            ? $this->getContainerIdentifiersOfTaggedServiceByInterface()
            : $this->getContainerIdentifiersOfTaggedServiceByTag();

        if (!$items->valid()) {
            return $this->isLazy
                ? (static function () { yield from []; })()
                : [];
        }

        if (!$this->isLazy) {
            $services = [];

            foreach ($items as [$containerIdentifier, $item]) {
                $this->useKeys
                    ? $services[$this->getIdentifierFromTag($containerIdentifier, $item)] = $this->getContainer()->get($containerIdentifier)
                    : $services[] = $this->getContainer()->get($containerIdentifier);
            }

            return $services;
        }

        return $this->getServicesAsLazy($items);
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    /**
     * @param \Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}> $items
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getServicesAsLazy(\Generator $items): \Generator
    {
        foreach ($items as [$containerIdentifier, $item]) {
            if ($this->useKeys) {
                yield $this->getIdentifierFromTag($containerIdentifier, $item) => $this->getContainer()->get($containerIdentifier);
            } else {
                yield $this->getContainer()->get($containerIdentifier);
            }
        }
    }

    /**
     * @return \Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}>
     *
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getContainerIdentifiersOfTaggedServiceByTag(): \Generator
    {
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

            if ($definition->hasTag($this->tag)) {
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
     * @return \Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}>
     *
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getContainerIdentifiersOfTaggedServiceByInterface(): \Generator
    {
        $taggedServices = new \SplPriorityQueue();
        $taggedServices->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

        /** @var non-empty-string $containerIdentifier */
        foreach ($this->getContainer()->getDefinitions() as $containerIdentifier => $definition) {
            try {
                if ($definition instanceof DiDefinitionAutowireInterface
                    && $definition->getDefinition()->implementsInterface($this->tag)) {
                    $definition->setContainer($this->getContainer());
                    // 🚩 Tag with higher priority early in list.
                    $taggedServices->insert(
                        [$containerIdentifier, $definition],
                        $definition->geTagPriority(
                            $this->tag,
                            ['priority.default_method' => $this->priorityDefaultMethod]
                        )
                    );
                }
            } catch (AutowireExceptionInterface $e) {
                throw new ContainerException(message: $e->getMessage(), previous: $e);
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
    private function getIdentifierFromTag(string $identifier, DiDefinitionAutowireInterface|DiTaggedDefinitionInterface $taggedAs): string
    {
        if (null !== $this->key) {
            $this->keyOptimized ??= '' === \trim($this->key)
                ? throw new AutowireException('Argument $key must be non-empty string.')
                : $this->key;

            $optionKey = $taggedAs->getTag($this->tag)[$this->keyOptimized] ?? null;

            if (null !== $optionKey) {
                if (!\is_string($optionKey) || '' === \trim($optionKey)) {
                    throw new AutowireException(
                        \sprintf(
                            'Tag option "%s" for container identifier "%s" with tag "%s". The value must be non-empty string. Got: "%s".',
                            $this->keyOptimized,
                            $identifier,
                            $this->tag,
                            \var_export($optionKey, true)
                        )
                    );
                }

                if ($taggedAs instanceof DiDefinitionAutowireInterface && \str_starts_with($optionKey, 'self::')) {
                    $method = \explode('::', $optionKey)[1];
                    $howGetOptions = \sprintf('Get key by "%s::%s()" for tag "%s".', $taggedAs->getDefinition()->name, $method, $this->tag);

                    // @phpstan-ignore return.type
                    return self::callStaticMethod($taggedAs, $method, true, $howGetOptions, ['string'], $this->tag, $taggedAs->getTag($this->tag) ?? []);
                }

                return $optionKey; // @phpstan-ignore return.type
            }

            return $taggedAs instanceof DiDefinitionAutowireInterface
                ? $this->getKeyByDefaultMethod($identifier, $taggedAs)
                : $identifier;
        }

        return $taggedAs instanceof DiDefinitionAutowireInterface
            ? $this->getKeyByDefaultMethod($identifier, $taggedAs)
            : $identifier;
    }

    /**
     * @param non-empty-string $identifier
     *
     * @return non-empty-string
     */
    private function getKeyByDefaultMethod(string $identifier, DiDefinitionAutowireInterface $taggedAs): string
    {
        if (null === $this->keyDefaultMethod) {
            return $identifier;
        }
        // @todo check method must be none-empty string?

        $howGetKeyOption = \sprintf('Get default key by "%s::%s()" for tag "%s".', $taggedAs->getDefinition()->name, $this->keyDefaultMethod, $this->tag);

        $key = self::callStaticMethod($taggedAs, $this->keyDefaultMethod, false, $howGetKeyOption, ['string'], $this->tag, $taggedAs->getTag($this->tag) ?? []);

        // @phpstan-ignore return.type
        return null === $key || '' === $key
            ? $identifier
            : $key;
    }
}
