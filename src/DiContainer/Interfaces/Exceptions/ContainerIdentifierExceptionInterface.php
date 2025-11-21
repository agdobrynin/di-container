<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Exceptions;

use Psr\Container\ContainerExceptionInterface;

interface ContainerIdentifierExceptionInterface extends ContainerExceptionInterface
{
    public function getIdentifier(): mixed;

    public function getDefinition(): mixed;
}
