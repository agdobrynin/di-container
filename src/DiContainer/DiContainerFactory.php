<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class DiContainerFactory implements Interfaces\DiContainerFactoryInterface
{
    public static function make(
        iterable $definitions = [],
        string $delimiterForNotationParamAndClass = '@'
    ): DiContainerInterface {
        return new DiContainer(
            definitions: $definitions,
            autowire: new Autowired(),
            linkContainerSymbol: $delimiterForNotationParamAndClass,
        );
    }
}
