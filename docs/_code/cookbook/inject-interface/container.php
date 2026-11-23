<?php
declare(strict_types=1);

// file: /app/src/container.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->qux);
