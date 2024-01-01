<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class Lorem
{
    public function __construct(
        #[Inject]
        public SimpleDbInterface $simpleDb
    ) {}
}
