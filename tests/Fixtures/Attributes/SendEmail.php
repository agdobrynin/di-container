<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Factory;
use Kaspi\DiContainer\Attributes\Inject;

class SendEmail
{
    public function __construct(
        #[Inject('@app.emails.admin')]
        public string $adminEmail,
        #[Inject('@app.logger')]
        public Logger $logger,
        #[Factory(FactoryObject::class)]
        public ?array $fromFactory = null,
    ) {}
}
