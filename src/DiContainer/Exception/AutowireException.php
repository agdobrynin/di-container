<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContextExceptionInterface;
use Kaspi\DiContainer\Traits\ContextExceptionTrait;

class AutowireException extends ContainerException implements AutowireExceptionInterface, ContextExceptionInterface
{
    use ContextExceptionTrait;
}
