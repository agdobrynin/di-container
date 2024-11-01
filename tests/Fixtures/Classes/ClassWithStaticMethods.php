<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

class ClassWithStaticMethods
{
    public static function langWelcomeMessage(array $dict, string $lang): string
    {
        return $dict[$lang];
    }

    public static function doSomething(): \stdClass
    {
        return (object) [
            'name' => 'John Doe',
            'age' => 32,
            'gender' => 'male',
        ];
    }
}
