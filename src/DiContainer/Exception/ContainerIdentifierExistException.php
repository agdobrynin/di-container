<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\ContainerIdentifierExistExceptionInterface;
use RuntimeException;

final class ContainerIdentifierExistException extends RuntimeException implements ContainerIdentifierExistExceptionInterface {}
