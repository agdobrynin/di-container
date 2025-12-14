<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

interface CompiledContainerFQN
{
    public function getNamespace(): string;

    public function getClass(): string;

    public function getFQN(): string;
}
