<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class DiContainerFactory implements DiContainerFactoryInterface
{
    public static function make(
        iterable $definitions = [],
        string $linkContainerSymbol = '@',
        string $delimiterAccessArrayNotationSymbol = '.'
    ): DiContainerInterface {
        return new DiContainer(
            definitions: $definitions,
            autowire: new Autowired(),
            linkContainerSymbol: $linkContainerSymbol,
            delimiterAccessArrayNotationSymbol: $delimiterAccessArrayNotationSymbol,
        );
    }
}
