<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\ServiceByReference;

#[ServiceByReference(ByReferenceFirstInterface::class)]
interface ByReferenceSecondInterface
{
    public function getName(): string;
}
