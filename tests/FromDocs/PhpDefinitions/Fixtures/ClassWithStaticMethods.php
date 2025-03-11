<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

use stdClass;

class ClassWithStaticMethods
{
    public static function doSomething(
        ServiceLocation $serviceLocation // Внедрение зависимости по типу
    ): stdClass {
        return (object) [
            'name' => 'John Doe',
            'age' => 32,
            'gender' => 'male',
            'city' => $serviceLocation->locationCity,
        ];
    }
}
