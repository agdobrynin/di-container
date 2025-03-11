<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use LogicException;

class AutowireException extends LogicException implements AutowireExceptionInterface {}
