<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface {}
