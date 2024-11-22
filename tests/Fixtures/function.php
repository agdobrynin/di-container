<?php

declare(strict_types=1);

namespace Tests\Fixtures;

function funcWithDependencyClass(ClassWithSimplePublicProperty $class, ?string $append = null): string
{
    return $class->publicProperty.($append ? ' + '.$append : '');
}
