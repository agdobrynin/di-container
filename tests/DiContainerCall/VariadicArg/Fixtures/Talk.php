<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class Talk
{
    public static function staticMethodByReference(
        #[Inject('word.first')]
        #[Inject('word.second')]
        WordInterface ...$word
    ): array {
        return $word;
    }

    public static function staticMethodByClass(
        #[Inject(WordSuffix::class)]
        #[Inject(WordHello::class)]
        WordInterface ...$word
    ): array {
        return $word;
    }
}
