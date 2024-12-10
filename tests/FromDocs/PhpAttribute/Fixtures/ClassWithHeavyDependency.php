<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\ProxyClosure;

class ClassWithHeavyDependency
{
    /**
     * @param Closure(): HeavyDependency $heavyDependency
     */
    public function __construct(
        #[ProxyClosure(HeavyDependency::class)]
        private \Closure $heavyDependency,
        private LiteDependency $liteDependency,
    ) {}

    public function doHeavyDependency(): string
    {
        return ($this->heavyDependency)()->doMake();
    }

    public function doLiteDependency(): string
    {
        return $this->liteDependency->doMake();
    }
}
