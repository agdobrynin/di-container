<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

trait ArgumentsForResolvingTrait
{
    protected array $arguments = [];

    /**
     * @var \ReflectionParameter[]
     */
    protected array $reflectedArguments = [];

    public function getArgumentsForResolving(): array
    {
        return \array_reduce(
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
}
