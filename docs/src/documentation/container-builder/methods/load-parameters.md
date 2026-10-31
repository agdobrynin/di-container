---
outline: [2, 3]
---
# Регистрация параметров контейнера { #title }

## Обзор { #overview }

Конфигурацию параметров контейнера можно представить в виде коллекции ключ-значение, где ключ это строковое имя параметра, а значение представлено одним из [поддерживаемых типов](../../container-parameters/index.md#parameter-support-types).

> [!IMPORTANT]
> Все добавленные ранее параметры в конфигурацию могут быть замены новыми значениями если имя параметра совпадает.

### Пример { #example }

PHP классы:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

В файле конфигураций `report_parameters.php` объявить скалярные значения для параметров контейнера:
- `'params.report_output_dir'` – директория вывода отчётов.
- `'params.last_days'` – количество последних дней используемых при формировании отчёта.

В файле конфигураций `report_services_with_params.php` объявлены PHP классы с внедрением скалярных параметров через параметры контейнера.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/report_services_with_params.php')// [!code highlight]
    ->loadParameters('/app/config/report_parameters.php')// [!code ++]
    ->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
var_dump($service->lastDays);
```

<<< @/_code/config/report_parameters.php

<<< @/_code/config/report_services_with_params.php

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string (9) "/var/data"
int(10)
```

> [!NOTE]
> Прочитайте главу «[параметры контейнера](../../container-parameters/index.md)».

## loadParameters()

Метод `loadParameters()` выполняет регистрацию параметров контейнера из файлов:

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::loadParameters(
    string $file,
    string ...$_
): static
```

Параметры:

- `$file` – полный путь к файлу.
- `$_` – полный путь к файлу.

## addParameters()

Метод `addParameters()` выполняет регистрацию параметров контейнера из коллекции.

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::addParameters(
    iterable $params
): static
```

Параметры:
- `$params` – коллекция ключ-значение конфигурации параметров.

## setParameter()

Метод `setParameter()` выполняет регистрацию параметра контейнера и его значения.

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder\DiContainerBuilder::setParameter(
    string $name,
    array|int|float|string|bool|null|\UnitEnum $value
): static
```

Параметры:
- `$name` – имя параметра.
- `$value` – значение параметра.

<!--@include: ../../_include/term_notes.md-->
