<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCompileExceptionInterface;
use RuntimeException;

final class DiDefinitionCompileException extends RuntimeException implements DiDefinitionCompileExceptionInterface {}
