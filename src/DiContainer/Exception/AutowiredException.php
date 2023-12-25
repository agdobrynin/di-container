<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowiredExceptionInterface;

class AutowiredException extends \LogicException implements AutowiredExceptionInterface {}
