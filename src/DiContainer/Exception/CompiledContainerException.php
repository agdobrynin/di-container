<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\CompiledContainerExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

final class CompiledContainerException extends CompiledContainerExceptionAbstract implements ContainerExceptionInterface, CompiledContainerExceptionInterface {}
