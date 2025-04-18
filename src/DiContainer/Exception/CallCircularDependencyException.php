<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class CallCircularDependencyException extends RuntimeException implements ContainerExceptionInterface {}
