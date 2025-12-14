<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;

interface ContainerCompilerInterface
{
    public function getOutputDirectory(): string;

    public function getContainerFQN(): CompiledContainerFQN;

    public function getDefinitionTransformer(): DiDefinitionTransformerInterface;

    /**
     * @throws DefinitionCompileExceptionInterface
     */
    public function compile(): string;
}
