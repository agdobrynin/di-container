<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

function funcWithDependencyClass(
    ClassWithSimplePublicProperty $class,
    #[Inject('service.append')]
    ?string $append = null
): string {
    return $class->publicProperty.($append ? ' + '.$append : '');
}

function functionResolveArgumentByName(#[Inject] array $allUsers): string
{
    return \implode(' - ', \array_map(static fn (string $item) => \strtoupper($item), $allUsers));
}
