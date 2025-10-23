<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixtures;

final class Two
{
    public static function getDefaultKey(): string
    {
        return 'services.key_default';
    }

    public static function getDefaultKeyWrongReturnType(): One
    {
        return new One();
    }
}
