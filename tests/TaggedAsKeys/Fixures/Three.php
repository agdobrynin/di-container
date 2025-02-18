<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixures;

final class Three
{
    public static function getKey(): string
    {
        return 'service.three.method';
    }

    public static function getDefaultKey(): string
    {
        return 'service.three.default';
    }
}
