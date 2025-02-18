<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixures\Attributes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.other', options: ['key' => 'main', 'key.method' => 'self::getKey'], priority: 1000)]
final class Three
{
    public static function getKey(): string
    {
        return 'signed_service';
    }
}
