<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;

class SendEmail
{
    public function __construct(
        #[Inject('@emails.admin')]
        public string $adminEmail,
        #[DiFactory(DiFactoryObject::class)]
        public ?array $fromFactory = null,
    ) {}
}
