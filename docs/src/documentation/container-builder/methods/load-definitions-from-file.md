# Объявление определений контейнера из файлов конфигураций { #title }

## Обзор { #overview }

[Файлы конфигураций](../configuration_files.md) помогают разделить и сгруппировать объявление определений контейнера. Используя разные файлы конфигураций можно по условиям собирать нужные файлы и передавать в сборку контейнера.

## load()

Метод `load()` объявляет определения в контейнере из файлов конфигураций с отслеживанием уникальности идентификаторов контейнера [^ContainerId].

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder::load(
    string $file,
    string ...$_
): static;
```
Параметры:
- `$file` – полный путь к файлу конфигураций.
- `$_` – полный путь к файлу конфигураций.

> [!WARNING]
> <!--@include: ../_include/build_id_equals.md-->

### Пример использования { #example-load }

PHP классы:

::: code-group

<<< @/_code/src/Storages/ReportStorage.php

<<< @/_code/src/Services/ReportMaker.php

:::

В файле конфигураций `report_services.php` объявлены PHP классы `\App\Storages\ReportStorage` с внедрением скалярной зависимости в параметр `$dir` и `\App\Services\ReportMaker` с внедрением скалярной зависимости в параметр `$lastDays`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/report_services.php')//[!code ++]
    ->build();

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

```text
string (9) "/var/data"
int(10)
```

> [!TIP]
> Для объявления PHP классов используем [хелпер функцию `diAutowire()`](../../php-definition/di-autowire.md).


## loadOverride()

Для некоторых сценариев использования может понадобиться переопределение ранее объявленных определений по тому же идентификатору контейнера [^ContainerId].

Метод `loadOverride()` объявляет определения контейнера из файлов конфигураций с перезаписью ранее объявленных определений если у них совпадают идентификаторы.

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder::loadOverride(
    string $file,
    string ...$_
): static;
```

Параметры:
- `$file` – полный путь к файлу конфигураций.
- `$_` – полный путь к файлу конфигураций.

### Пример использования { #example-load-override }

PHP классы:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

В файле конфигураций `report_services.php` объявлены PHP класс `\App\Storages\ReportStorage` с внедрением скалярной зависимости в параметр `$dir` и `\App\Services\ReportMaker` с внедрением скалярной зависимости в параметр `$lastDays`, но если определена переменная среды `APP_ENV` со значением `'dev'` для этих классов необходимо объявить внедрение скалярных зависимостей с другими значениями, т.е. перезаписать ранее объявленные определения с новыми значениями для тех же идентификаторов контейнера[^ContainerId]. 

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

use function {getenv, putenv};


putenv('APP_ENV=dev');//[!code ++]

$builder = (new DiContainerBuilder())
    ->load('/app/config/report_services.php');// [!code highlight]

if ('dev' === getenv('APP_ENV')) {//[!code ++]
    $builder->loadOverride('/app/config/dev/services.php');//[!code ++]
}//[!code ++]

$container = $builder->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
var_dump($service->lastDays);
```

<<< @/_code/config/report_services.php

```php [dev/services.php]
<?php
declare(strict_types=1);

// file: /app/config/dev/services.php // [!code highlight]

use App\Storages\ReportStorage;
use App\Services\ReportMaker;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(ReportMaker::class)
        ->bindArguments(
            lastDays: 1
        );

    yield diAutowire(ReportStorage::class)
        ->bindArguments(
            dir: '/tmp'
        );
};
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```text
string (4) "/tmp"
// int(1)
```

> [!TIP]
> Для объявления PHP классов используем [хелпер функцию `diAutowire()`](../../php-definition/di-autowire.md).

<!--@include: ../../_include/term_notes.md-->
