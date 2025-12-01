<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

final class Foo
{
    public function __construct(public readonly string $code) {}

    public static function config(
        #[Inject('config.secure_code')]
        string $configCode
    ): Foo {
        return new self($configCode);
    }
}
