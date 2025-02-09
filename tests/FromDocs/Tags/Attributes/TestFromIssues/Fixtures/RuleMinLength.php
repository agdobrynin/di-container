<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\TestFromIssues\Fixtures;

final class RuleMinLength implements RuleInterface
{
    public function __construct(private int $min = 5) {}

    public function validate(string $text): string
    {
        if (\strlen($text) >= $this->min) {
            return $text;
        }

        throw new \LogicException(
            \sprintf('Invalid string. Minimal length %d characters. Got: %d characters', $this->min, \strlen($text))
        );
    }

    public static function getPriority(): int
    {
        return 10;
    }
}
