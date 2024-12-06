<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class WordVariadicDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): mixed
    {
        return $container->get(WordSuffix::class);
    }
}
