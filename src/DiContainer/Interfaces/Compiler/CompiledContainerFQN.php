<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler;

interface CompiledContainerFQN
{
    /**
     * Namespace for compiled container class.
     * If namespace not defined return empty string.
     * Example:
     *
     *      class-string `\App\Services\CompiledContainer`
     *      namespace `App\Services`
     */
    public function getNamespace(): string;

    /**
     * Class name compiled container.
     *
     * @return class-string
     */
    public function getClass(): string;

    /**
     * Fully qualified name of compiled container with lead `\`.
     * Example:
     *
     *     Fully qualified class name '\App\Services\CompiledContainer'
     *
     * @return non-empty-string
     */
    public function getFQN(): string;
}
