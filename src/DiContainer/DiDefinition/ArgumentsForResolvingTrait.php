<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

trait ArgumentsForResolvingTrait
{
    private array $arguments = [];

    /**
     * @var \ReflectionParameter[]
     */
    private array $reflectedArguments = [];
    private array $argumentsForResolving = [];

    public function getArgumentsForResolving(): array
    {
        if ([] === $this->argumentsForResolving) {
            $this->argumentsForResolving = \array_reduce(
                $this->reflectedArguments,
                function (array $arguments, \ReflectionParameter $p): array {
                    if (isset($this->arguments[$p->name])) {
                        $argPrepared = $p->isVariadic() && \is_array($this->arguments[$p->name])
                            ? $this->arguments[$p->name]
                            : [$this->arguments[$p->name]];

                        return \array_merge($arguments, $argPrepared);
                    }

                    return \array_merge($arguments, [$p]);
                },
                []
            );
        }

        return $this->argumentsForResolving;
    }
}
