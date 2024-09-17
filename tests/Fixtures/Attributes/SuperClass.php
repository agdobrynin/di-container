<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(DiFactorySuperClass::class)]
class SuperClass
{
    public function __construct(public string $name, public int $age) {}

    public function getArray(
        #[DiFactory(FirstArrayIteratorDiFactory::class)]
        \ArrayIterator $arrayIterator
    ): array {
        return $arrayIterator->getArrayCopy();
    }
}
