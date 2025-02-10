<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions\Fixtures;

class RuleB implements RuleInterface
{
    public static function getPriorityOther(): string
    {
        return 'ZZZ';
    }
}
