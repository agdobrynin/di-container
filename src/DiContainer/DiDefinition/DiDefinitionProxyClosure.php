<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionProxyClosureInterface;
use Kaspi\DiContainer\Traits\PsrContainerTrait;

final class DiDefinitionProxyClosure implements DiDefinitionProxyClosureInterface
{
    use PsrContainerTrait;

    /**
     * @param non-empty-string $definition
     */
    public function __construct(private string $definition) {}

    public function invoke(): \Closure
    {
        if (!$this->getContainer()->has($this->getDefinition())) {
            throw new AutowireException("Definition \"{$this->definition}\" does not exist");
        }

        return function () { // @phan-suppress-current-line PhanUnreferencedClosure
            return $this->container->get($this->getDefinition());
        };
    }

    public function getDefinition(): string
    {
        if ('' !== \trim($this->definition)) {
            return $this->definition;
        }

        throw new AutowireException('Definition for '.__CLASS__.' must be non-empty string.');
    }
}
