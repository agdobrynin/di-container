<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Kaspi\DiContainer\Exception\AutowiredException;
use Kaspi\DiContainer\Interfaces\FactoryInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER)]
final class Factory
{
    /**
     * @param class-string<FactoryInterface> $id
     */
    public function __construct(public string $id, public array $arguments = [])
    {
        \is_a($this->id, FactoryInterface::class, true)
            or throw new AutowiredException("Factory attribute '{$this->id}' must be a ".FactoryInterface::class);
    }
}
