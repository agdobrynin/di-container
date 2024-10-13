<?php

declare(strict_types=1);

namespace Tests\Unit\Callable\Fixtures;

use Psr\Container\ContainerInterface;

function testFunction(\ArrayIterator $iterator, ContainerInterface $container): string
{
    $iterator->append($container->get('test'));

    return $iterator->serialize();
}
