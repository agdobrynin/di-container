<?php

declare(strict_types=1);

use Tests\ContainerBuilder\Fixtures2\Bat;

use function Kaspi\DiContainer\diAutowire;

return static function () {
    yield diAutowire(Bat::class);
};
