<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionClosureInterface;
use Kaspi\DiContainer\Traits\PsrContainerTrait;

final class DiDefinitionClosure implements DiDefinitionClosureInterface
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
        static $trimmedDefinition;

        return '' !== ($trimmedDefinition ??= \trim($this->definition))
            ? $this->definition
            : throw new AutowireException('Definition for '.__CLASS__.' must be non-empty string.');
    }
}
