<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireAttributeException;

use function preg_match;
use function sprintf;

trait SetupTrait
{
    private string $method;

    /** @var string[] */
    private array $arguments = [];

    /**
     * @param string ...$argument Container identifiers for arguments of setup method.
     */
    public function __construct(string ...$argument)
    {
        $this->arguments = $argument;
    }

    /**
     * Container identifiers for arguments of setup method.
     *
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

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
