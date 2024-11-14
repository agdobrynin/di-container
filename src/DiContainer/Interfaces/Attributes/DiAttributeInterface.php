<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\Attributes;

interface DiAttributeInterface
{
    public function getId(): string;

    public function isSingleton(): bool;

    public function getArguments(): array;
}
