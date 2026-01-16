<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
use RuntimeException;

final class ContainerBuilderException extends RuntimeException implements ContainerBuilderExceptionInterface {}
