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
    public function __construct(
        private string $tag,
        private bool $isLazy = true,
        private ?string $defaultPriorityMethod = null,
        private bool $requireDefaultPriorityMethod = false
    ) {}

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
            return $this->isLazy
                ? (static function () { yield from []; })()
                : [];
        }

        if (!$this->isLazy) {
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
            if (false === ($definition instanceof DiTaggedDefinitionInterface)) {
                continue;
            }

            if ($definition instanceof DiDefinitionInvokableInterface) {
                $definition->setContainer($this->getContainer());
            }

            if ($definition->hasTag($this->tag)) {
                // 🚩 Tag with higher priority early in list.
                if ($definition instanceof DiTaggedDefinitionAutowireInterface) {
                    $taggedServices->insert(
                        $containerIdentifier,
                        $definition->geTagPriority($this->tag, $this->defaultPriorityMethod, $this->requireDefaultPriorityMethod)
                    );
                } else {
                    $taggedServices->insert(
                        $containerIdentifier,
                        $definition->geTagPriority($this->tag)
                    );
                }
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
        $taggedServices = new \SplPriorityQueue();
        $taggedServices->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

        foreach ($this->getContainer()->getDefinitions() as $containerIdentifier => $definition) {
            try {
                if ($definition instanceof DiTaggedDefinitionAutowireInterface
                    && ($reflectionClass = $definition->getDefinition()) instanceof \ReflectionClass
                    && $reflectionClass->implementsInterface($this->tag)) {
                    $definition->setContainer($this->getContainer());
                    // 🚩 Tag with higher priority early in list.
                    $taggedServices->insert($containerIdentifier, $definition->geTagPriority($this->tag, $this->defaultPriorityMethod, $this->requireDefaultPriorityMethod));
                }
            } catch (AutowireExceptionInterface $e) {
                throw new ContainerException(message: $e->getMessage(), previous: $e);
            }
        }

        foreach ($taggedServices as $containerIdentifier) {
            yield $containerIdentifier;
        }
    }
}
