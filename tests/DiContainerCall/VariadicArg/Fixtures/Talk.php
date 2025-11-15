<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;

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

    public static function staticMethodByReferenceOneToMany(
        #[Inject('services.words')]
        WordInterface ...$word
    ): array {
        return $word;
    }

    public static function staticMethodInjectByCallable(
        #[InjectByCallable(WordVariadicDiFactory::class)]
        WordInterface ...$word
    ): array {
        return $word;
    }

    /**
     * @param WordInterface ...$wordService
     */
    public static function staticMethodByArgumentNameOneToMany(
        ...$wordService
    ): array {
        return $wordService;
    }
}
