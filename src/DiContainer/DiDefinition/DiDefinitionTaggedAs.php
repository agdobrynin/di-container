<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    use DiContainerTrait;

    private bool $tagIsInterface;

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
         * @var \Generator<non-empty-string> $containerIdentifiers
         */
        $containerIdentifiers = $this->tagIsInterface
            ? $this->getContainerIdentifiersOfTaggedServiceByInterface()
            : $this->getContainerIdentifiersOfTaggedServiceByTag();

        if (!$containerIdentifiers->valid()) {
            return $this->isLazy
                ? (static function () { yield from []; })()
                : [];
        }

        if (!$this->isLazy) {
            $services = [];

            while ($containerIdentifiers->valid()) {
                $containerIdentifier = $containerIdentifiers->current();
                $this->useKeys
                    ? $services[$containerIdentifier] = $this->getContainer()->get($containerIdentifier)
                    : $services[] = $this->getContainer()->get($containerIdentifier);
                $containerIdentifiers->next();
            }

            return $services;
        }

        return $this->getServicesAsLazy($containerIdentifiers);
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    /**
     * @param \Generator<non-empty-string> $containerIdentifiers
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getServicesAsLazy(\Generator $containerIdentifiers): \Generator
    {
        foreach ($containerIdentifiers as $containerIdentifier) {
            if ($this->useKeys) {
                yield $containerIdentifier => $this->getContainer()->get($containerIdentifier);
            } else {
                yield $this->getContainer()->get($containerIdentifier);
            }
        }
    }

    /**
     * @return \Generator<non-empty-string>
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

                if ($definition instanceof DiTaggedDefinitionAutowireInterface) {
                    $operationOptions['priority.default_method'] = $this->priorityDefaultMethod;
                }

                // 🚩 Tag with higher priority early in list.
                $taggedServices->insert($containerIdentifier, $definition->geTagPriority($this->tag, $operationOptions));
            }
        }

        /** @var non-empty-string $containerIdentifier */
        foreach ($taggedServices as $containerIdentifier) {
            yield $containerIdentifier;
        }
    }

    /**
     * @return \Generator<non-empty-string>
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
                if ($definition instanceof DiTaggedDefinitionAutowireInterface
                    && $definition->getDefinition()->implementsInterface($this->tag)) {
                    $definition->setContainer($this->getContainer());
                    // 🚩 Tag with higher priority early in list.
                    $taggedServices->insert(
                        $containerIdentifier,
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

        /** @var non-empty-string $containerIdentifier */
        foreach ($taggedServices as $containerIdentifier) {
            yield $containerIdentifier;
        }
    }
}
