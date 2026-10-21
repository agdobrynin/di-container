# Регистрация параметров контейнера { #title }

## Обзор { #overview }

Конфигурацию параметров контейнера можно представить в виде коллекции ключ-значение, где ключ это строковое имя параметра, а значение представлено одним из [поддерживаемых типов](../../container-parameters/index.md#parameter-support-types).

> [!IMPORTANT]
> Все добавленные ранее параметры в конфигурацию могут быть замены новыми значениями если имя параметра совпадает.

### Пример { #example }

Объявить параметры контейнера `'params.report_output_dir'` для директории вывода отчетов PHP класса `\App\Storages\ReportStorage::$dir` и `'params.last_days'` для количества последних дней формирования отчёта PHP класса `\App\Services\ReportMaker::$lastDays`.

::: code-group


```php [container_build.php]
// file: /app/src/container_build.php
require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/report_services_with_params.php')// [!code highlight]
    ->loadParameters('/app/config/report_parameters.php')// [!code ++]
    // переопеделит ранее объявленный параметр
    ->setParameter('params.last_days', 15)// [!code ++]
    ->build();

$service = $container->get(ReportMaker::class);

var_dump($service->storage->dir);
// string (9) "/var/data"

var_dump($service->lastDays);
// int(15)
```

<<< @/_code/config/report_parameters.php#snippet{1}

<<< @/_code/config/report_services_with_params.php#snippet{1}

<<< @/_code/src/Services/ReportMaker.php#snippet{1}

<<< @/_code/src/Storages/ReportStorage.php#snippet{1}

:::

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
