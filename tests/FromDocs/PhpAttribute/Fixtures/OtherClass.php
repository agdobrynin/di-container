<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

final class OtherClass
{
    public function __construct(
        #[DiFactory(ClassOneDiFactory::class)]
        public readonly ClassOne $classOne
    ) {}
}
