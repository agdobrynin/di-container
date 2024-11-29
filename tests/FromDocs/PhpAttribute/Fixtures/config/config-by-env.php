<?php

declare(strict_types=1);
use Psr\Container\ContainerInterface;

use function Kaspi\DiContainer\diCallable;

return [
    'services.file' => diCallable(
        static fn (ContainerInterface $container) => match (\getenv('APP_TEST_FILE')) {
            'prod' => $container->get('services.file.prod'),
            default => $container->get('services.file.local')
        }
    ),
];
