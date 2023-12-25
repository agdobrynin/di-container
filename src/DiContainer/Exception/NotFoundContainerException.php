<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundContainerException extends \RuntimeException implements NotFoundExceptionInterface {}
