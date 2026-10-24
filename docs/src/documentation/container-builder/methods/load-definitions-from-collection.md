# Объявление определений контейнера через коллекцию { #title }

## Обзор { #overview }

Объявить определения контейнера можно без использования файлов конфигураций – используя коллекцию. Коллекция должна быть представлена любым `iterable` типом.

Коллекция предоставляет идентификатор контейнера [^ContainerId] как ключ коллекции и значение – объявление определения контейнера.

## addDefinitions()

Метод `addDefinitions()` объявляет определения контейнера с отслеживанием уникальности идентификаторов контейнера [^ContainerId] в рамках всей конфигурации контейнера.

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitions(
    iterable $definitions
): static;
```
Параметры:
- `$definitions` – коллекция определений.

> [!WARNING]
> <!--@include: ../_include/build_id_equals.md-->

Пример использования:

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\diAutowire;

// Анонимная функция в качестве коллекции
$fn = static function (): Generator {// [!code highlight]
    yield diAutowire(ReportStorage::class)// [!code highlight]
        ->bindArguments(// [!code highlight]
            dir: '/var/data'// [!code highlight]
        );// [!code highlight]
};// [!code highlight]

// PHP массив в качестве коллекции
$services = [// [!code highlight]
    diAutowire(ReportMaker::class)// [!code highlight]
        ->bindArguments(// [!code highlight]
            lastDays: 10// [!code highlight]
        )// [!code highlight]
];// [!code highlight]

$container = (new DiContainerBuilder())
    ->addDefinitions(($fn)())// [!code ++]
    ->addDefinitions($services)// [!code ++]
    ->build();


$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
// string (9) "/var/data"

var_dump($service->lastDays);
// int(10)
```

<<< @/_code/src/Services/ReportMaker.php
<<< @/_code/src/Storages/ReportStorage.php

::: 

> [!TIP]
> Для объявления PHP классов используем [хелпер функцию `diAutowire()`](../../php-definition/di-autowire.md).


## addDefinitionsOverride()

Метод `addDefinitionsOverride()` объявляет определения из коллекции с перезаписью ранее объявленных определений если у них совпадают идентификаторы контейнера[^ContainerId].

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder::addDefinitionsOverride(
    iterable $definitions
): static;
```
Параметры:
- `$definitions` – коллекция определений.


::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\diAutowire;
use function {getenv, putenv};

putenv('APP_ENV=dev');//[!code ++]

$builder = (new DiContainerBuilder())
    ->load('/app/config/report_services.php');

if ('dev' === getenv('APP_ENV')) {
    $devServices = [//[!code ++]
        diAutowire(ReportStorage::class)//[!code ++]
            ->bindArguments(dir: '/tmp')//[!code ++]
    ];//[!code ++]

    $builder->addDefinitionsOverride($devServices);//[!code ++]
}

$container = $builder->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
// string (4) "/tmp"

var_dump($service->lastDays);
// int(10)
```
<<< @/_code/config/report_services.php

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

<!--@include: ../../_include/term_notes.md-->
