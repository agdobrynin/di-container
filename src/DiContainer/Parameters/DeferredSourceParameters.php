<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

use Closure;

final class DeferredSourceParameters extends AbstractSourceParameters
{
    /**
     * @var array<non-empty-string, SourceParameterItem>
     */
    private array $parameters;

    /**
     * @var null|Closure(): iterable<non-empty-string, mixed>
     */
    private ?Closure $sourceParameters;

    /**
     * @param callable(): iterable<non-empty-string, mixed> $sourceParameters
     */
    public function __construct(callable $sourceParameters)
    {
        $this->sourceParameters = $sourceParameters(...);
    }

    protected function &initializerParameters(): array
    {
        if (null !== $this->sourceParameters) {
            $this->parameters = [];

            foreach (($this->sourceParameters)() as $name => $parameter) {
                $this->parameters[$name] = new SourceParameterItem($name, $parameter, false);
            }

            $this->sourceParameters = null;
        }

        return $this->parameters;
    }
}
