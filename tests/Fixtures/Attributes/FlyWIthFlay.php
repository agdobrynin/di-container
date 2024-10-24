<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\DiFactory;

class FlyWIthFlay
{
    public function __construct(
        #[DiFactory(FlyClassByDiFactory::class, isSingleton: false)]
        public FlyClass $fly1,
        #[DiFactory(FlyClassByDiFactory::class, isSingleton: false)]
        public FlyClass $fly2,
    ) {}
}
