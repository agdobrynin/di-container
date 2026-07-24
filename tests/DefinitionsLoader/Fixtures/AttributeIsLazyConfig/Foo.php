<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\AttributeIsLazyConfig;

final class Foo
{
    public function __construct(public readonly Bar $bar) {}
}
