<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class NamesWithInject
{
    public function __construct(
        #[Inject('@app.users')]
        public array $names,
        #[Inject('@app.city')]
        public string $place,
        #[Inject]
        public SimpleDbInterface $simpleDb,
        #[Inject('@app.sites.search')]
        public ?string $site = null,
    ) {}
}
