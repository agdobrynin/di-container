<?php

declare(strict_types=1);

namespace Tests\AttributeReader\AttributeOnParameter\Fixtures;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class FooAttr
{
    public function __construct(public string $id) {}
}
