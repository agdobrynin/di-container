<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\KeyGeneratorForNamedParameterInterface;

final class KeyGeneratorForNamedParameter implements KeyGeneratorForNamedParameterInterface
{
    public function __construct(
        protected string $delimiterForNotationParamAndClass = '@'
    ) {}

    public function delimiter(): string
    {
        return $this->delimiterForNotationParamAndClass;
    }

    public function id(string $className, string $methodName, string $argName): string
    {
        return implode(
            $this->delimiterForNotationParamAndClass,
            [$className, $methodName, $argName]
        );
    }

    public function idConstructor(string $className, string $argName): string
    {
        return implode(
            $this->delimiterForNotationParamAndClass,
            [$className, self::METHOD_CONSTRUCTOR, $argName]
        );
    }
}
