<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\SetupAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

final class SetupOnMethods
{
    #[Setup('x')]
    public function __construct(private string $value) {}

    #[Setup]
    public function __destruct() {}

    #[Setup(new DiGet('services.foo'))]
    public function demo2(): void {}

    #[Setup('foo', new DiGet('services.bar'))]
    private function demo(): void {}

    #[Setup('foo', new DiGet('services.bar'))]
    private function demo3(): void {}
}
