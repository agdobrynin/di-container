<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class SendEmail
{
    public function __construct(
        #[Inject('@app.emails.admin')]
        public string $adminEmail,
        #[Inject('@app.logger')]
        public Logger $logger,
    ) {}
}
