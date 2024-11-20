<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\InjectContext;
use Kaspi\DiContainer\Attributes\InjectByReference;

class SendEmail
{
    public function __construct(
        #[InjectByReference('emails.admin')]
        public string $adminEmail,
        #[DiFactory(DiFactoryObject::class)]
        public ?array $fromFactory = null,
    ) {}
}
