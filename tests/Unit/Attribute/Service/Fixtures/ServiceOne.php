<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

class ServiceOne implements ByReferenceInterface
{
    public function __construct(public string $name) {}
}
