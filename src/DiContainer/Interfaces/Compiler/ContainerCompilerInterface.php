<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use RuntimeException;

interface ContainerCompilerInterface
{
    /**
     * @return non-empty-string
     *
     * @throws RuntimeException file operation exception
     */
    public function getOutputDirectory(): string;

    /**
     * @throws InvalidArgumentException
     */
    public function getContainerFQN(): CompiledContainerFQN;

    /**
     * @throws DefinitionCompileExceptionInterface
     */
    public function compile(): string;

    /**
     * @return non-empty-string full path to compiled container file
     *
     * @throws DefinitionCompileExceptionInterface
     * @throws RuntimeException                    file operation exception
     */
    public function compileToFile(): string;

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException file operation exception
     */
    public function fileNameForCompiledContainer(): string;
}
