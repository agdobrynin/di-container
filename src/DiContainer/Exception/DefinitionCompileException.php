<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use RuntimeException;

final class DefinitionCompileException extends RuntimeException implements DefinitionCompileExceptionInterface {}
