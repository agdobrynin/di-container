<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\Traits\DefinitionIdentifierTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    use DiContainerTrait;
    use DefinitionIdentifierTrait;

    private bool $tagIsInterface;

    /**
     * @param non-empty-string $tag
     */
    public function __construct(private string $tag, private bool $lazy = true) {}

    /**
     * @throws ContainerNeedSetExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getServicesTaggedAs(): iterable
    {
        $this->tagIsInterface ??= \interface_exists($this->tag);
        $containerIdentifiers = $this->tagIsInterface
            ? $this->getContainerIdentifiersOfTaggedServiceByInterface()
            : $this->getContainerIdentifiersOfTaggedServiceByTag();

        if (!$containerIdentifiers->valid()) {
            return $this->lazy
                ? (static function () { yield from []; })()
                : [];
        }

        if (!$this->lazy) {
            $services = [];

            while ($containerIdentifiers->valid()) {
                $services[] = $this->getContainer()->get($containerIdentifiers->current());
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
        while ($containerIdentifiers->valid()) {
            yield $this->getContainer()->get($containerIdentifiers->current());
            $containerIdentifiers->next();
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

        foreach ($this->getContainer()->getDefinitions() as $containerIdentifier => $definition) {
            if ($definition instanceof DiTaggedDefinitionInterface
                && $definition->hasTag($this->tag)) {
                // 🚩 Tag with higher number in 'priority' key being early in list.
                $taggedServices->insert($containerIdentifier, $definition->getOptionPriority($this->tag));
            }
        }

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
        foreach ($this->getContainer()->getDefinitions() as $containerIdentifier => $definition) {
            try {
                if ($definition instanceof DiDefinitionAutowire
                    && $definition->getDefinition()->implementsInterface($this->tag)) {
                    yield $containerIdentifier;
                }
            } catch (AutowireExceptionInterface $e) {
                throw new ContainerException(message: $e->getMessage(), previous: $e);
            }
        }
    }
}
