<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;

final class ParameterException extends ContainerException implements ParameterExceptionInterface {}
