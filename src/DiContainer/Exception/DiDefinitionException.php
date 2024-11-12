<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;

class DiDefinitionException extends \RuntimeException implements DiDefinitionExceptionInterface {}
