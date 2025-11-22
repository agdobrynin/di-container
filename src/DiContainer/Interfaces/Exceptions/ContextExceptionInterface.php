<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Exceptions;

use Psr\Container\ContainerExceptionInterface;

interface ContextExceptionInterface extends ContainerExceptionInterface
{
    /**
     * @return array<non-negative-int|string, mixed>
     */
    public function getContext(): array;

    /**
     * @return $this
     */
    public function setContext(mixed ...$context): self;
}
