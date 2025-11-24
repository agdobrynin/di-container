<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCallableExceptionInterface;

final class DiDefinitionCallableException extends DiDefinitionException implements DiDefinitionCallableExceptionInterface {}
