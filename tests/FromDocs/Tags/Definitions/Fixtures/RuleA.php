<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Definitions\Fixtures;

class RuleA implements RuleInterface
{
    public static function getPriority(string $tag, array $tagOptions): int|string|null
    {
        return match ($tag) {
            'tags.rules' => 'AAA',
            'tags.validation' => 1000,
            default => null,
        };
    }
}
