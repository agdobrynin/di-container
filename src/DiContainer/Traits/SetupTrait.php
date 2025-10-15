<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;

use function preg_match;
use function sprintf;

trait SetupTrait
{
    /** @var non-empty-string */
    private string $method;

    /** @var (DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionInvokableInterface|mixed)[] */
    private array $arguments = [];

    /**
     * @param (DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionInvokableInterface|mixed) ...$argument
     */
    public function __construct(mixed ...$argument)
    {
        $this->arguments = $argument;
    }

    /**
     * @return (DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionInvokableInterface|mixed)[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->method ?? throw new AutowireAttributeException('The private value $method is not defined.');
    }

    /**
     * @param non-empty-string $method
     */
    public function setMethod(string $method): void
    {
        // @see https://www.php.net/manual/en/language.variables.basics.php
        if (1 !== preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $method)) {
            throw new AutowireAttributeException(sprintf('The $method parameter must be a valid method name. Got: %s', $method));
        }

        $this->method = $method;
    }
}
