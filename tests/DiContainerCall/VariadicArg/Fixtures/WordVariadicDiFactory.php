<?php

declare(strict_types=1);

namespace Tests\DiContainerCall\VariadicArg\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class WordVariadicDiFactory implements DiFactoryInterface
{
    /**
     * @return WordInterface[]
     */
    public function __invoke(ContainerInterface $container): array
    {
        return [
            $container->get(WordSuffix::class),
            $container->get(WordHello::class),
        ];
    }
}
