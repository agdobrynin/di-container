<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionSetupInterface extends DiDefinitionArgumentsInterface
{
    /**
     * Call setter method for class with input arguments.
     * Calling method may use autowire feature.
     * This method can be used many times.
     * Arguments provided by the user added by name or index.
     *
     * User can set arguments by named argument:
     *
     *       setup('classMethod', var1: 'value 1', var2: 'value 2')
     *       // bind parameters by name Class->classMethod(var1: 'value 1', var2: 'value 2')
     *
     * User can set arguments by index argument:
     *
     *      setup('classMethod', 'value 1', 'value 2')
     *      // bind parameters by index Class->classMethod('value 1', 'value 2')
     *
     * @param non-empty-string                                                                          $method
     * @param DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionInvokableInterface|mixed $argument
     *
     * @return $this
     */
    public function setup(string $method, mixed ...$argument): static;
}
