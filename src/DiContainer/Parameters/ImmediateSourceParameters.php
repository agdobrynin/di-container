<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;

/**
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
final class ImmediateSourceParameters extends AbstractSourceParameters
{
    /**
     * @var array<non-empty-string, array{0: false, 1:mixed}|array{0: true, 1: SourceParameterType}>
     */
    private array $parameters = [];

    /**
     * @param iterable<non-empty-string, mixed> $parameters
     */
    public function __construct(iterable $parameters = [])
    {
        foreach ($parameters as $name => $parameter) {
            $this->parameters[$name] = [false, $parameter];
        }
    }

    protected function &internalParameters(): array
    {
        return $this->parameters;
    }
}
