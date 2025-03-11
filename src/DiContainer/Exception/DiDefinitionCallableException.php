<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;
use RuntimeException;

class DiDefinitionCallableException extends RuntimeException implements DiDefinitionCallableExceptionInterface {}
