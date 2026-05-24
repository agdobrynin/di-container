<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

final class ImmediateSourceParameters extends AbstractSourceParameters
{
    /**
     * @var array<non-empty-string, SourceParameterItem>
     */
    private array $parameters;

    /**
     * @param iterable<non-empty-string, mixed> $parameters
     */
    public function __construct(iterable $parameters = [])
    {
        $this->parameters = [];
        foreach ($parameters as $name => $parameter) {
            $this->parameters[$name] = new SourceParameterItem($name, $parameter, false);
        }
    }

    protected function &initializerParameters(): array
    {
        return $this->parameters;
    }
}
