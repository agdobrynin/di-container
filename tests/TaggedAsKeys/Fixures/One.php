<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixures;

use stdClass;

final class One
{
    public static function getKey(string $tag): string
    {
        return match ($tag) {
            'tags.one' => 'service.one',
            default => 'something',
        };
    }

    public static function getKeyFail(): array|stdClass
    {
        return ['ok', 'yap'];
    }
}
