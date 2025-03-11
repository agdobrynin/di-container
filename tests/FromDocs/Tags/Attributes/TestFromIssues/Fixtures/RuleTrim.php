<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\TestFromIssues\Fixtures;

use function trim;

final class RuleTrim implements RuleInterface
{
    public function validate(string $text): string
    {
        return trim($text);
    }

    public static function getPriority(): int
    {
        return 100;
    }
}
