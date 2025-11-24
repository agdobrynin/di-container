<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Kaspi\DiContainer\Traits\ContextExceptionTrait;

final class ContainerIdentifierException extends ContainerException implements ContainerIdentifierExceptionInterface
{
    use ContextExceptionTrait;
}
