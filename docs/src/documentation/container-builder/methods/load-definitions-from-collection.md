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

```php {10-15,18-23} [container_build.php]
// file: /app/src/container_build.php
require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Generator;
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\diAutowire;

// Анонимная функция в качестве коллекции
$fn = static function (): Generator {
    yield diAutowire(ReportStorage::class)
        ->bindArguments(
            dir: '/var/data'
        );
};

// PHP массив в качестве коллекции
$services = [
    diAutowire(ReportMaker::class)
        ->bindArguments(
            lastDays: 10
        )
];

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

<<< @/_code/src/Services/ReportMaker.php#snippet{1}

<<< @/_code/src/Storages/ReportStorage.php#snippet{1}

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
// file: /app/src/container_build.php
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
<<< @/_code/config/report_services.php#snippet{1}

<<< @/_code/src/Services/ReportMaker.php#snippet{1}

<<< @/_code/src/Storages/ReportStorage.php#snippet{1}

:::

<!--@include: ../../_include/term_notes.md-->
