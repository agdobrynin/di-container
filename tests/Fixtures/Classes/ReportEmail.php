<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

readonly class ReportEmail
{
    public function __construct(public string $adminEmail, public int $delay) {}

    public function emailWith(): string
    {
        return "admin<{$this->adminEmail}>";
    }
}
