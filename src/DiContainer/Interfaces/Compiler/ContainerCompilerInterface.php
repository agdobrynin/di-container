<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

interface ContainerCompilerInterface
{
    public function getOutputDirectory(): string;

    public function getContainerClass(): string;

    public function getContainerNamespace(): string;

    public function compile(): string;
}
