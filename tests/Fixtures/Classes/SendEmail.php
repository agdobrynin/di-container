<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class SendEmail
{
    public function __construct(
        public string $adminEmail,
        public bool $confirm = true,
        public ?Logger $logger = null,
    ) {}
}
