<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\InjectLazyObjectOnParameter;

use Kaspi\DiContainer\Attributes\Autowire;

final class Foo
{
    public function __construct(
        #[Autowire(arguments: ['bar value'], isLazy: true)]
        public readonly Bar $bar,
        #[Autowire(Baz::class, arguments: ['baz value'], isLazy: true)]
        public readonly ServiceInterface $baz,
    ) {}
}
