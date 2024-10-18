<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Psr\Container\ContainerExceptionInterface;

class CallCircularDependency extends \RuntimeException implements ContainerExceptionInterface {}
