<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

final class DiFactoryObject implements DiFactoryInterface
{
    public function __construct(
        #[Inject]
        private InjectSimpleArgument $injectSimpleArgument
    ) {}

    public function __invoke(ContainerInterface $container): array
    {
        return $this->injectSimpleArgument->arrayIterator()->getArrayCopy();
    }
}
