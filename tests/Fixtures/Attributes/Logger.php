<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class Logger
{
    public function __construct(
        #[Inject('@app.logger_file')]
        public string $file
    ) {}
}
