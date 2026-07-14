<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\AttributeResetterConfig;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime(resetter: 'reset')]
final class Bar
{
    public function reset(): void {}
}
