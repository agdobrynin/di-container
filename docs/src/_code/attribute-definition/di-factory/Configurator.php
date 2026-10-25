<?php
declare(strict_types=1);

// file: /app/src/Helpers/Configurator.php

namespace App\Helpers;

use App\Services\Bar;

final class Configurator
{
    public function __construct(public readonly Bar $bar) {}

    public function quxDoConfig(): Qux
    {
        $calculatedValue = $this->bar->calculateSomething();

        return '' === $calculatedValue
            ? new Qux('Lorem ipsum')
            : new Qux($calculatedValue);
    }
}
