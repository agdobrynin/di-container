<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Rules;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$rules = $container->get(Rules::class);
var_dump($rules->rules);
