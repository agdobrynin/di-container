<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(ClassOneDiFactory::class, isSingleton: true)]
class ClassOne
{
    public function __construct(public string $name, public int $age) {}
}
