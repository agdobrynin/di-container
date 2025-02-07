<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions\Fixtures;

class RuleA implements RuleInterface
{
    public static function getPriority(): string
    {
        return 'AAA';
    }
}
