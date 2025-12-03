<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\Arguments\ArgumentBuilderInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

/**
 * @phpstan-type DiDefinitionType DiDefinitionArgumentsInterface|DiDefinitionAutowireInterface|DiDefinitionInterface|DiDefinitionLinkInterface|DiDefinitionSetupAutowireInterface|DiDefinitionSingletonInterface|DiDefinitionTagArgumentInterface|DiDefinitionTaggedAsInterface|DiTaggedDefinitionInterface
 * @phpstan-type BindArgumentsType array<non-empty-string|non-negative-int, DiDefinitionType|mixed>
 */
interface DiDefinitionArgumentsInterface
{
    /**
     * Arguments provided by the user added by name or index.
     * User can set.
     *
     *      bindArguments(var1: 'value 1', var2: 'value 2')
     *      // bind parameters by name $var1 = 'value 1', $var2 = 'value 2'
     *
     * ⚠ This method replaces all previously defined arguments.
     *
     * @param (DiDefinitionType|mixed) ...$argument
     *
     * @return $this
     */
    public function bindArguments(mixed ...$argument): static;

    /**
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeArgumentBuilder(DiContainerInterface $container): ?ArgumentBuilderInterface;
}
