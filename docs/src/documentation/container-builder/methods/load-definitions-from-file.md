# Объявление определений контейнера из файлов конфигураций { #title }

## Обзор { #overview }

[Конфигурационный файл](../configuration_files.md) может возвращать объявления определений для контейнера как любой `iterable` тип.

Объявления определений также возможно через [конфигуратор определений контейнера](../definitions-configurator/index.md) внутри конфигурационного файла.


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
- `$file` – полный путь к файлу конфигурации определений.
- `$_` – полный путь к файлу конфигурации определений.

> [!IMPORTANT]
> При сборке контейнера методом `DiContainerBuilder::build()` при совпадении идентификаторов контейнера будет выброшено исключение.
>

Пример использования:

::: code-group

```php [container_build.php]
// file: /app/src/container_build.php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
// string (9) "/var/data"
```

<<< _code/services.php#snippet{1}

<<< _code/ReportMaker.php#snippet{1}

<<< _code/ReportStorage.php#snippet{1}

:::


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
- `$file` – полный путь к файлу конфигурации определений.
- `$_` – полный путь к файлу конфигурации определений.

Пример использования:

::: code-group

```php [container_build.php]
// file: /app/src/container_build.php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

use function {getenv, putenv};


putenv('APP_ENV=DEV');//[!code ++]

$builder = (new DiContainerBuilder())
    ->load('/app/config/services.php');

if ('dev' === getenv('APP_ENV')) {//[!code ++]
    $builder->loadOverride('/app/config/dev/services.php');//[!code ++]
}//[!code ++]

$container = $builder->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
// string (4) "/tmp"
```

<<< _code/services.php#snippet{1}

```php [dev/services.php]
// file: /app/config/dev/services.php
use App\Services\ReportMaker;
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(ReportStorage::class)
        ->bindArguments(
            dir: '/tmp'
        );
};
```

<<< _code/ReportMaker.php#snippet{1}

<<< _code/ReportStorage.php#snippet{1}

:::

> [!TIP]
> Для объявления PHP классов используем [хелпер функцию `diAutowire()`](../../php-definition/di-autowire.md).

<!--@include: ../../_include/term_notes.md-->
