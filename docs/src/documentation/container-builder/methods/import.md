# Объявление PHP-классов в контейнере из файловой системы { #title }

## Обзор { #overview }

Объявить PHP-классы и интерфейсы в контейнере можно в автоматическом режиме через сканирование файловой системы. При анализе PHP-классов и интерфейсов из файловой системы учитываются примененные атрибуты если в конфигурации контейнера указано [использование PHP атрибутов](../../container-config/index.md#use-attribute).

## import()

Метод `import()` объявляет PHP-классы из указанных директорий файловой системы.

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

- `$namespace` – префикс пространства имён PHP-классов.
- `$src` – директория источник.
- `$excludeFiles` – исключить из объявления PHP-классы в находящиеся в файлах.
- `$availableExtensions` – расширения файлов PHP-классов.

> [!NOTE]
> Параметр `$excludeFiles` использует синтаксис шаблонов из [php функции `\fnmatch()`](https://www.php.net/manual/en/function.fnmatch.php).

PHP-классы найденные в исключенных файлах, параметр `$excludeFiles`, будут недоступны контейнеру через метод `get()`, а следовательно нельзя будет внедрить такой PHP-класс.

Объявить PHP-классы можно из нескольких директорий вызывая последовательно метод `import()` с указанием уникального значения в параметре `$namespace`:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
 
$builder = (new DiContainerBuilder())
  ->import(namespace: 'App\Services\\', src: '/app/src/Services')
  ->import(namespace: 'App\Actions\\', src: '/app/src/Actions');
```

## Пример объявления PHP-классов из файловой системы { #example }

PHP-классы:

::: code-group

<<< @/_code/src/Services/ReportBuilder.php

<<< @/_code/src/Interfaces/ReportsStorageInterface.php

<<< @/_code/src/Storages/ReportsStorage.php

<<< @/_code/src/Various/Qux.php

:::

Необходимо объявить PHP-классы из директории проекта `'/app/src'`. PHP-классы в директории `'/app/src/Various/'` необходимо исключить из контейнера.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

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
var_dump($reportBuilder->lastDays);
var_dump($container->has(Qux::class));

$container->get(Qux::class); //[!code error]
```

<<< @/_code/config/report_parameters.php

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string (9) "/var/data"
int(10)
bool(false)
PHP Warning:  Uncaught Kaspi\DiContainer\Exception\NotFoundException: No entry was found for 'App\Various\Qux' identifier.
```

<!--@include: ../../_include/term_notes.md-->
