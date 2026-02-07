<?php

declare(strict_types=1);

namespace Tests\AttributeReader\DiFactory\Fixtures;

final class FooFactoryWithArgs
{
    public static function create(string $apiKey): mixed {}
}
