<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\DefaultPriorityMethodWrong;

use Kaspi\DiContainer\Attributes\Tag;
use stdClass;

#[Tag('tags.bat')]
final class Baz
{
    public static function getPriorityDefaultOne()
    {
        return new stdClass();
    }

    public static function getPriorityDefaultTwo()
    {
        return [100];
    }
}
