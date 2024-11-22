<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class ClassWithSimplePublicProperty
{
    public function __construct(
        #[Inject('vars.public-property')]
        public string $publicProperty
    ) {}

    public function __invoke(?string $append = null): string
    {
        return $this->publicProperty.($append ? ' invoke '.$append : '');
    }

    public function method(?string $append = null): string
    {
        return $this->publicProperty.($append ? ' method '.$append : '');
    }

    public static function staticMethod(string $append = ''): string
    {
        return 'static method '.$append;
    }
}
