<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

/**
 * @phpstan-import-type SourceParameterResolvedType from AbstractSourceParameters
 * @phpstan-import-type SourceParameterRawType from AbstractSourceParameters
 */
final class ImmediateSourceParameters extends AbstractSourceParameters
{
    /**
     * @var array<non-empty-string, SourceParameterRawType|SourceParameterResolvedType>
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
