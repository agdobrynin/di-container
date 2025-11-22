<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixtures\Failed;

final class Foo
{
    public function getKeyNoneStatic(): string
    {
        return 'key_one';
    }

    private static function getKeyStaticPrivate(): string
    {
        return 'key_four';
    }

    private static function getKeyStaticProtected(): string
    {
        return 'key_three';
    }

    private function getKeyNoneStaticProtected(): string
    {
        return 'key_two';
    }

    private function getKeyNoneStaticPrivate(): string
    {
        return 'key_two';
    }
}
