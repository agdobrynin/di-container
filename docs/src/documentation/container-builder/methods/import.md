# Объявление PHP классов в контейнере из файловой системы { #title }

## Обзор { #overview }

Объявить PHP классы и интерфейсы в контейнере можно в автоматическом режиме через сканирование файловой системы. При анализе PHP классов и интерфейсов из файловой системы учитываются примененные атрибуты если в конфигурации контейнера указано [использование PHP атрибутов](../../container-config/index.md#use-attribute).

## import()

Метод `import()` объявляет PHP классы из указанных директорий файловой системы.

Сигнатура метода:

```php
\Kaspi\DiContainer\DiContainerBuilder::import(
    string $namespace,
    string $src,
    array $excludeFiles = [],
    array $availableExtensions = ['php'],
): static;
```

Параметры:

- `$namespace` – префикс пространства имён PHP классов.
- `$src` – директория источник.
- `$excludeFiles` – исключить из объявления PHP классы в находящиеся в файлах.
- `$availableExtensions` – расширения файлов PHP классов.

> [!NOTE]
> Параметр `$excludeFiles` использует синтаксис шаблонов из [php функции `\fnmatch()`](https://www.php.net/manual/en/function.fnmatch.php).

Объявить PHP классы можно из нескольких директорий вызывая последовательно метод `import()` с указанием уникального значения в параметре `$namespace`:

```php
use Kaspi\DiContainer\DiContainerBuilder;
 
$builder = (new DiContainerBuilder())
  ->import(namespace: 'App\Services\\', src: '/app/src/Services')
  ->import(namespace: 'App\Actions\\', src: '/app/src/Actions');
```

PHP классы найденные в исключенных файлах, параметр `$excludeFiles`, будут недоступны контейнеру через метод `get()`, а следовательно нельзя будет внедрить такой PHP класс.


## Пример объявления PHP классов из файловой системы { #example }

Необходимо объявить PHP классы из директории проекта `'/app/src'`. PHP классы в директории `''/app/src/Various/'` необходимо исключить из контейнера.  

::: code-group

```php [container_build.php]
// file: /app/src/container_build.php
require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportBuilder;
use App\Various\Qux;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/report_parameters.php')//[!code highlight]
    ->import(// [!code ++]
        namespace: 'App\\',// [!code ++]
        src: '/app/src/',// [!code ++]
        excludeFiles: [// [!code ++]
            '/app/src/Various/*.php',// [!code ++]
        ]// [!code ++]
    )// [!code ++]
    ->build();

$reportBuilder = $container->get(ReportBuilder::class);

var_dump($reportBuilder->storage->dir);
// string (9) "/var/data"

var_dump($reportBuilder->lastDays);
// int(10)

var_dump($container->has(Qux::class));
// bool(false)

$container->get(Qux::class); //[!code error]
// throw exception: `\Kaspi\DiContainer\Exception\NotFoundException`
```
<<< @/_code/config/report_parameters.php#snippet{1}

<<< @/_code/src/Services/ReportBuilder.php#snippet{1}

<<< @/_code/src/Interfaces/ReportsStorageInterface.php#snippet{1}

<<< @/_code/src/Storages/ReportsStorage.php#snippet{1}

<<< @/_code/src/Various/Qux.php#snippet{1}

:::

<!--@include: ../../_include/term_notes.md-->
