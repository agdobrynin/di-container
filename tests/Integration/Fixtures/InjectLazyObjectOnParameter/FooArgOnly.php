<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\InjectLazyObjectOnParameter;

use Kaspi\DiContainer\Attributes\Autowire;

final class FooArgOnly
{
    public function __construct(
        #[Autowire(arguments: ['bar value'])]
        public readonly Bar $bar,
        #[Autowire(BazArgOnly::class, arguments: ['baz value'])]
        public readonly ServiceInterface $baz,
    ) {}
}
