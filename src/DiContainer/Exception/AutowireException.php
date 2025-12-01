<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;

class AutowireException extends ContainerException implements AutowireExceptionInterface {}
