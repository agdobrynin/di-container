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
     * @var array<non-empty-string, array{0: bool, 1:SourceParameterType}>
     */
    private array $parameters = [];

    /**
     * @param iterable<non-empty-string, SourceParameterType> $parameters
     * @param iterable<non-empty-string, mixed>               $removedParameters
     */
    public function __construct(iterable $parameters = [], iterable $removedParameters = [])
    {
        $removedNames = [];

        foreach ($removedParameters as $name => $v) {
            $removedNames[$name] = true;
        }

        foreach ($parameters as $name => $parameter) {
            if (!isset($removedNames[$name])) {
                $this->parameters[$name] = [false, $parameter];
            }
        }
    }

    protected function &internalParameters(): array
    {
        return $this->parameters;
    }
}
