<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;

/**
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
final class DeferredSourceParameters extends AbstractSourceParameters
{
    /**
     * @var callable(): iterable<non-empty-string, SourceParameterType>
     */
    private $sourceParameters;

    /**
     * @var array<non-empty-string, array{0: bool, 1:SourceParameterType}>
     */
    private array $parameters;

    /**
     * @param callable(): iterable<non-empty-string, SourceParameterType> $sourceParameters
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
