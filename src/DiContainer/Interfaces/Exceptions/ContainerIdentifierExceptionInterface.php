<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Exceptions;

use Psr\Container\ContainerExceptionInterface;

interface ContainerIdentifierExceptionInterface extends ContainerExceptionInterface
{
    /**
     * @return array<non-negative-int|string, mixed>
     */
    public function getContext(): array;
}
