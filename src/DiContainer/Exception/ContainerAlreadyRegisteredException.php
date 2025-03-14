<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerAlreadyRegisteredExceptionInterface;
use RuntimeException;

class ContainerAlreadyRegisteredException extends RuntimeException implements ContainerAlreadyRegisteredExceptionInterface {}
