<?php

declare(strict_types=1);

namespace Tests\Fixtures;

function funcWithDependencyClass(ClassWithSimplePublicProperty $class, string $append): string
{
    return $class->publicProperty.' + '.$append;
}
