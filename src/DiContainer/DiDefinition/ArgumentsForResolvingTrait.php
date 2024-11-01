<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

trait ArgumentsForResolvingTrait
{
    protected function prepareArgumentsForResolving(array $reflectionArguments, array $predefinedArguments): array
    {
        return \array_reduce(
            $reflectionArguments,
            static function (array $arguments, \ReflectionParameter $p) use ($predefinedArguments): array {
                if (isset($predefinedArguments[$p->name])) {
                    $argSource = $predefinedArguments[$p->name];
                    $argPrepared = $p->isVariadic() && \is_array($argSource) ? $argSource : [$argSource];

                    return \array_merge($arguments, $argPrepared);
                }

                return \array_merge($arguments, [$p]);
            },
            []
        );
    }
}
