<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\ServiceByReference;

#[ServiceByReference(ByReferenceSecondInterface::class)]
interface ByReferenceFirstInterface
{
    public function getName(): string;
}
