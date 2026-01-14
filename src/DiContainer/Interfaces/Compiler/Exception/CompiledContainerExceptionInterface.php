<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Compiler\Exception;

use Psr\Container\ContainerExceptionInterface;

interface CompiledContainerExceptionInterface extends ContainerExceptionInterface
{
    /**
     * @return list<array{
     *       exceptionType: string,
     *       message: string,
     *       file: string,
     *       line: int,
     *       code: int,
     *       trace_as_string: string
     *   }>
     */
    public function getExceptionStack(): array;
}
