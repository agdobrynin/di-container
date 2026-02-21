<?php

declare(strict_types=1);

namespace Tests\AttributeReader\SetupAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\SetupPriority;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

final class SetupImmutablePriorityOnMethods
{
    #[SetupImmutable('bar')]
    public function __construct(private string $value) {}

    #[SetupImmutable]
    #[SetupPriority(100)]
    public function __destruct() {}

    #[SetupImmutable(new DiGet('services.foo'))]
    #[SetupPriority(20)]
    public function demo2(): self {}

    #[SetupImmutable('foo', new DiGet('services.bar'))]
    private function demo(): self {}

    #[SetupImmutable('foo', new DiGet('services.bar'))]
    private function demo3(): self {}
}
