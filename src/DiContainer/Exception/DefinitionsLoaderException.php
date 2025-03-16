<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use RuntimeException;

final class DefinitionsLoaderException extends RuntimeException implements DefinitionsLoaderExceptionInterface {}
