<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;

use function preg_match;
use function sprintf;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 * @phpstan-import-type SetupConfigureArgumentsType from SetupConfigureTrait
 */
trait SetupAttributeTrait
{
    /** @var SetupConfigureArgumentsType */
    public readonly array $arguments;

    /** @var non-empty-string */
    private string $method;

    public function __construct(mixed ...$argument)
    {
        /**
         * @phpstan-var SetupConfigureArgumentsType $argument
         */
        $this->arguments = $argument;
    }

    /**
     * @return non-empty-string
     *
     * @throws AutowireExceptionInterface
     */
    public function getMethod(): string
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
