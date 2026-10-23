<?php
declare(strict_types=1);

// file: /app/src/container.php// [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/callable_entries.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->value);
// string(26) "Lorem ipsum dolor sit amet"
