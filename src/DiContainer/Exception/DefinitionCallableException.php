<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionCallableExceptionInterface;

class DefinitionCallableException extends \RuntimeException implements DefinitionCallableExceptionInterface {}
