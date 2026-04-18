<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

/**
 * @phpstan-import-type SourceParameterResolvedType from AbstractSourceParameters
 * @phpstan-import-type SourceParameterRawType from AbstractSourceParameters
 */
final class DeferredSourceParameters extends AbstractSourceParameters
{
    /**
     * @var callable(): iterable<non-empty-string, mixed>
     */
    private $sourceParameters;

    /**
     * @var array<non-empty-string, SourceParameterRawType|SourceParameterResolvedType>
     */
    private array $parameters;

    /**
     * @param callable(): iterable<non-empty-string, mixed> $sourceParameters
     */
    public function __construct(callable $sourceParameters)
    {
        $this->sourceParameters = $sourceParameters;
    }

    protected function &internalParameters(): array
    {
        if (!isset($this->parameters)) {
            $this->parameters = [];

            foreach (($this->sourceParameters)() as $name => $parameter) {
                $this->parameters[$name] = [false, $parameter];
            }

            unset($this->sourceParameters);
        }

        return $this->parameters;
    }
}
