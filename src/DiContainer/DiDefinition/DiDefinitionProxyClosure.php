<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Closure;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use Kaspi\DiContainer\Traits\TagsTrait;

use function sprintf;
use function trim;

final class DiDefinitionProxyClosure implements DiDefinitionInvokableInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface
{
    use DiContainerTrait;
    use TagsTrait;

    /**
     * @var non-empty-string
     */
    private string $verifyDefinition;

    /**
     * @param non-empty-string $definition
     */
    public function __construct(private string $definition, private ?bool $isSingleton = null) {}

    public function isSingleton(): ?bool
    {
        return $this->isSingleton;
    }

    public function invoke(): Closure
    {
        if (!$this->getContainer()->has($this->getDefinition())) {
            throw new AutowireException(sprintf('Definition "%s" does not exist.', $this->getDefinition()));
        }

        return function () {
            return $this->container->get($this->getDefinition());
        };
    }

    /**
     * @return non-empty-string
     */
    public function getDefinition(): string
    {
        return $this->verifyDefinition ??= '' === trim($this->definition)
            ? throw new AutowireException(sprintf('Definition for "%s" must be non-empty string.', __CLASS__))
            : $this->definition;
    }
}
