<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Attributes;

interface DiAttributeServiceInterface extends DiAttributeInterface
{
    public function isSingleton(): bool;

    public function getArguments(): array;
}
