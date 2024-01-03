<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Kaspi\DiContainer\Attributes\Inject;

class NamesWithInject
{
    public function __construct(
        #[Inject('app.users')]
        public array $names,
        #[Inject('app.city')]
        public string $place,
        #[Inject('app.sites.search')]
        public ?string $site = null,
    ) {}
}
