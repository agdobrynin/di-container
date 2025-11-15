<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg\Fixtures;

use Psr\Container\ContainerInterface;

class WordVariadicDiFactory
{
    public function __invoke(ContainerInterface $container): mixed
    {
        return $container->get(WordSuffix::class);
    }
}
