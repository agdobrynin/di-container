<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

interface DiDefinitionArgumentsInterface extends DiDefinitionTagArgumentInterface
{
    /**
     * Add input argument by index or name.
     * If argument is variadic then $value must be wrap array.
     *
     * ⚠ This method replaces the previously defined argument with the same name.
     *
     * @param non-empty-string|non-negative-int                                                         $name
     * @param DiDefinitionArgumentsInterface|DiDefinitionInterface|DiDefinitionInvokableInterface|mixed $value
     *
     * @return $this
     *
     * @deprecated Use method bindArguments(). This method will remove next major release.
     */
    public function addArgument(int|string $name, mixed $value): static;

    /**
     * Arguments provided by the user.
     * Each item in arguments array must provide a variable name in item key and value.
     *
     * ⚠ This method replaces all previously defined arguments.
     *
     * For example:
     *
     *       [
     *           // raw value
     *           "paramNameOne" => "some value",    // include scalar types, array, null type.
     *           "paramNameTwo" => $definition,     // definition implement DiDefinitionInterface
     *                                              // include DiDefinitionAutowireInterface.
     *       ]
     *
     * @deprecated Use method bindArguments(). This method will remove next major release.
     *
     * @param array<non-empty-string|non-negative-int, mixed> $arguments
     *
     * @return $this
     */
    public function addArguments(array $arguments): static;

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
