<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__ . '/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->load('/app/config/services.php')//[!code ++]
    ->loadParameters('/app/config/parameters.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->logger);//[!code warning]

$bar = $container->get(Bar::class);
var_dump($bar->logger);//[!code warning]
