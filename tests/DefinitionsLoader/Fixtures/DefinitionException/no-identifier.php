<?php

declare(strict_types=1);

use function Kaspi\DiContainer\diCallable;

return [
    diCallable(static fn () => 'ok'),
];
