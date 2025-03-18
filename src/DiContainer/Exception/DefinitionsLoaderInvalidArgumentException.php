<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;

final class DefinitionsLoaderInvalidArgumentException extends InvalidArgumentException implements DefinitionsLoaderExceptionInterface {}
