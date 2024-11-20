<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Attributes;

interface DiAttributeServiceInterface extends DiAttributeInterface
{
    /**
     * If the value provided by the user is a string
     * and the argument value starts with this character
     * then it is a container reference.
     */
    public const IS_REFERENCE = '@';

    public function isSingleton(): bool;

    /**
     * Arguments provided by the user.
     * Each item in arguments array must provide a variable name in item key and value.
     * For example:
     *
     *      [
     *          // raw value
     *          "paramNameOne" => "some value", // include scalar types, array, null type.
     *          // reference to container identifier
     *          // see constant self::IS_REFERENCE
     *          "paramNameTwo" => "@identifier",
     *      ]
     *
     * @return array<non-empty-string, mixed>
     */
    public function getArguments(): array;
}
