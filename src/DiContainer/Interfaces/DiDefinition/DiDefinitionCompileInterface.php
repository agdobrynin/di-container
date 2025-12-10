<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCompileExceptionInterface;

interface DiDefinitionCompileInterface
{
    /**
     * @throws DiDefinitionCompileExceptionInterface
     */
    public function compile(string $containerVariableName, DiContainerInterface $container): CompiledEntryInterface;
}
