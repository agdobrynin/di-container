<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface {}
