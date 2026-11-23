<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Qux};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->load('/app/config/services.php')//[!code ++]
    ->load('/app/config/services_qux.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->qux);

$qux = $container->get(Qux::class);
var_dump($qux->qux);
