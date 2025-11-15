<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

use function preg_match;
use function sprintf;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
trait SetupAttributeTrait
{
    /** @var non-empty-string */
    private string $method;

    /** @var (DiDefinitionType|mixed)[] */
    private array $arguments = [];

    /**
     * @param (DiDefinitionType|mixed) ...$argument
     */
    public function __construct(mixed ...$argument)
    {
        $this->arguments = $argument;
    }

    /**
     * @return (DiDefinitionType|mixed)[]
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
