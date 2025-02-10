<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions\Fixtures;

class RuleC
{
    public static function getCollectionPriority(): string
    {
        return 'BBB';
    }
}
