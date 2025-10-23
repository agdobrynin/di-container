<?php

declare(strict_types=1);

namespace Tests\TaggedAsKeys\Fixtures;

final class OptionKeyReturnEmptyString
{
    public static function getKeyEmpty(): string
    {
        return '';
    }

    public static function getKeySpaces(): string
    {
        return '   ';
    }
}
