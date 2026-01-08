<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;

interface ContainerCompilerInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getContainerFQN(): CompiledContainerFQN;

    /**
     * @throws DefinitionCompileExceptionInterface
     */
    public function compile(): string;
}
