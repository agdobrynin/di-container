<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces;

interface KeyGeneratorForNamedParameterInterface
{
    public const METHOD_CONSTRUCTOR = '__construct';

    public function id(string $className, string $methodName, string $argName): string;

    public function idConstructor(string $className, string $argName);
}
