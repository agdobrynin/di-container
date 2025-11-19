<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;

class DiDefinitionCallableException extends DiDefinitionException implements DiDefinitionCallableExceptionInterface {}
