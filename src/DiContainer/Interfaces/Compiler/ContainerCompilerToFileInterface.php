<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use RuntimeException;

interface ContainerCompilerToFileInterface
{
    public function getContainerCompiler(): ContainerCompilerInterface;

    /**
     * @return non-empty-string full path to compiled container file
     *
     * @throws DefinitionCompileExceptionInterface
     * @throws RuntimeException                    file operation exception
     */
    public function compileToFile(bool $rebuild = false): string;

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException file operation exception
     */
    public function fileNameForCompiledContainer(): string;

    /**
     * @return non-empty-string
     *
     * @throws RuntimeException file operation exception
     */
    public function getOutputDirectory(): string;
}
