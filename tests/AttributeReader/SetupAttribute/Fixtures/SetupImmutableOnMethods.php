<?php

declare(strict_types=1);

namespace Tests\AttributeReader\SetupAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;

final class SetupImmutableOnMethods
{
    #[SetupImmutable('bar')]
    public function __construct(private string $value) {}

    #[SetupImmutable]
    public function __destruct() {}

    #[SetupImmutable(new DiGet('services.foo'))]
    public function demo2(): self {}

    #[SetupImmutable('foo', new DiGet('services.bar'))]
    private function demo(): self {}

    #[SetupImmutable('foo', new DiGet('services.bar'))]
    private function demo3(): self {}
}
