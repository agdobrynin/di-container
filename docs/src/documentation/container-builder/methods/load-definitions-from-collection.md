---
outline: [2, 3]
---

# Объявление определений контейнера через коллекцию { #title }

## Обзор { #overview }

Объявить определения контейнера можно используя коллекцию. Коллекция должна быть представлена любым `iterable` типом. Элементы коллекции должны быть представлены парой ключ-значение, где ключ коллекции это идентификатор контейнера [^ContainerId], а значение – объявление определения.

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

### Пример использования метода addDefinitions() { #example-add-definitions }

PHP классы:

::: code-group
<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php
:::

Объявить через коллекцию PHP классы с внедрением скалярных параметров: `\App\Storages\ReportStorage` в параметр `$dir`  и `\App\Services\ReportMaker` в параметр `$lastDays`.

> [!TIP]
> <!--@include: _include/tip_use_di-autowire.md-->

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
var_dump($service->lastDays);
```

::: 

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```terminaloutput
string (9) "/var/data"
int(10)
```

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


### Пример использования метода addDefinitionsOverride() { #example-add-definitions-override }

PHP классы:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

В файле конфигураций `report_services.php` объявлены PHP классы с внедрением скалярных параметров: `\App\Storages\ReportStorage` в параметр `$dir` и `\App\Services\ReportMaker` в параметр `$lastDays`. Если значение переменной среды `APP_ENV` равно `'dev'`, то для класса `\App\Storages\ReportStorage` необходимо внедрить скалярное значение `'/tmp'` в параметр `$dir` через коллекцию, т.е. перезаписать ранее объявленное определение с новым значениям для того же идентификатора контейнера[^ContainerId].

> [!TIP]
> <!--@include: _include/tip_use_di-autowire.md-->

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
    ->load('/app/config/report_services.php');// [!code highlight]

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
var_dump($service->lastDays);
```
<<< @/_code/config/report_services.php

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```terminaloutput
string (4) "/tmp"
int(10)
```

<!--@include: ../../_include/term_notes.md-->
