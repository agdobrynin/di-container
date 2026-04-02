<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use RuntimeException;

final class ParameterException extends RuntimeException implements ParameterExceptionInterface {}
