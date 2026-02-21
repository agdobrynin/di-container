<?php

declare(strict_types=1);

namespace Tests\AttributeReader\SetupAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupPriority;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

final class SetupPriorityOnMethods
{
    #[Setup('x')]
    public function __construct(private string $value) {}

    #[Setup]
    public function __destruct() {}

    #[Setup(new DiGet('services.foo'))]
    #[SetupPriority(100)]
    public function demo2(): void {}

    #[SetupPriority(50)]
    #[Setup('bar', new DiGet('services.foo'))]
    private function demo(): void {}

    #[Setup('foo', new DiGet('services.bar'))]
    #[SetupPriority(100)]
    private function demo3(): void {}
}
