<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ResetterExceptionInterface;

final class ResetterException extends ContainerException implements ResetterExceptionInterface {}
