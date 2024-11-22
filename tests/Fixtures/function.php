<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

function funcWithDependencyClass(
    ClassWithSimplePublicProperty $class,
    #[Inject('service.append')]
    ?string $append = null
): string {
    return $class->publicProperty.($append ? ' + '.$append : '');
}

function functionWithVariadic(
    #[Inject('item.first')]
    #[Inject('item.second')]
    ClassWithSimplePublicProperty ...$item
): string {
    return \array_reduce($item, static function (string $carry, ClassWithSimplePublicProperty $class): string {
        return $carry.' / '.$class->publicProperty;
    }, '');
}

function functionResolveArgumentByName(#[Inject] array $allUsers): string
{
    return \implode(' - ', \array_map(static fn (string $item) => \strtoupper($item), $allUsers));
}
