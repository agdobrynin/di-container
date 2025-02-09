<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\TestFromIssues\Fixtures;

interface RuleInterface
{
    public function validate(string $text): string;
}
