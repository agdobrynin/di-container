<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\ServiceByReference;

#[ServiceByReference('       ')]
interface ByReferenceSpaceInIdentifierInterface
{
    public function getName(): string;
}
