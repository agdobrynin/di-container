<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionArgumentsInterface extends DiDefinitionTagArgumentInterface
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
     * @param DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionInvokableInterface|mixed ...$argument
     *
     * @return $this
     */
    public function bindArguments(mixed ...$argument): static;
}
