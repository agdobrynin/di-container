<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\DiFactory\Fixtures;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(ClassADiFactory::class)]
#[DiFactory(StubDiFactory::class)]
class ClassManyAttributeOnClass
{
    public function __construct() {}
}
