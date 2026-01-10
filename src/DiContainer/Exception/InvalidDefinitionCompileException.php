<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\InvalidDefinitionCompileExceptionInterface;

final class InvalidDefinitionCompileException extends InvalidArgumentException implements InvalidDefinitionCompileExceptionInterface {}
