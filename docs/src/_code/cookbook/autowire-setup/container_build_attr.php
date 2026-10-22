<?php
declare(strict_types=1);

// file: /app/src/container_build.php
require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooLogger, FileLogger};
use Psr\Log\LoggerInterface;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params_logger.php')// [!code highlight]
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$fooLogger = $container->get(FooLogger::class);
/** @var FileLogger $fileLogger */
$fileLogger = $fooLogger->getLogger();

var_dump($fileLogger instanceof LoggerInterface);
// (bool) true

var_dump($fileLogger instanceof FileLogger);
// (bool) true

var_dump($fileLogger->fileName);
// string(24) "/var/logs/app_logger.log"
