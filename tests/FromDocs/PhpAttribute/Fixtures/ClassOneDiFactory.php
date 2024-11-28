<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class ClassOneDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): ClassOne
    {
        return new ClassOne('Piter', 22);
    }
}
