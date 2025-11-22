<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContextExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\Traits\ContextExceptionTrait;

class DiDefinitionException extends ContainerException implements ContextExceptionInterface, DiDefinitionExceptionInterface
{
    use ContextExceptionTrait;
}
