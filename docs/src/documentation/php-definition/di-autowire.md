---
outline: [2, 4]
---
# diAutowire()

## Обзор { #overview }

Хелпер функция `diAutowire()` объявляет PHP класс в контейнере.

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\{
    DiDefinitionArgumentsInterface as Args,
    DiDefinitionSetupAutowireInterface as Setup,
    DiDefinitionTagArgumentInterface as Tag,
    DiDefinitionResetterSetterInterface as Resetter
};

\Kaspi\DiContainer\diAutowire(
    string $definition,
    ?bool $isSingleton = null,
    bool $isLazy = false,
): Args & Setup & Tag & Resetter
```
Параметры:
- `$definition` – FQCN [^FQCN] объявляемого PHP класса. Можно использовать безопасное объявление через магическую константу `::class` - `MyClass::class`
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$isLazy` – «ленивый объект». Подробнее в разделе – [Внедрение «ленивых» объектов контейнером](../14-lazy-injection.md).

Функция `diAutowire()` возвращает объект предоставляющий методы:
 - `bindArguments()` – аргументы конструктора PHP класса.
 - `setup()` – внедрение зависимостей через мутабельный сеттер-метод [^SetterMethodMutable].
 - `setupImmutable()` – внедрение зависимостей через иммутабельный сеттер-метод [^SetterMethodImmutable].
 - `bindTag()` – тег с метаданными.
 - `setResetter()` - конфигурация для сброса состояния объекта.

## bindArguments()

Аргументы для конструктора PHP класса.

<!--@include: ./_include/bind_arguments.md-->

### Пример внедрения зависимости через метод bindArguments() { #example-bind-arguments }

Необходимо через метод `bindArguments()` указать конструктору как внедрят зависимость в конкретный параметр:
- в параметр `$lastDays` класса `\App\Services\ReportMaker`.
- в параметр `$dir` класса `\App\Storages\ReportStorage`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/report_services.php')
    ->build();

$report = $container->get(ReportMaker::class);
var_dump($report->lastDays);
// int(10)

var_dump($report->storage->dir);
// string(9) "/var/data"
```
<<< @/_code/config/report_services.php

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

## Сеттер-методы внедрения зависимостей { #di-autowire-setter-method }

Помимо внедрения зависимостей через конструктор PHP класса существует возможность внедрения зависимостей через сеттер методы PHP класса.

> [!TIP]
> - Аргументы передавать не нужно если у сеттер-метода нет параметров или они могут быть внедрены автоматически на основе конфигурации.
> - Для внедрения скалярных типов зависимостей рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».
> - Аргументы сеттер-метода могут принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.


### setup()

Метод `setup()` внедряет зависимость в PHP класс через **мутабельный сеттер-метод** [^SetterMethodMutable], и описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`.

Сигнатура метода:

```php 
DiDefinitionSetupAutowireInterface::setup(
    string $method,
    array $arguments = []
)
``` 
Параметры:
- `$method` – имя сеттер-метода в классе.
- `$arguments` – аргументы к параметрам сеттер-метода.

> [!TIP]
Для неполного списка аргументов в параметре `$arguments` в качестве ключа в массиве нужно указать имя параметра сеттер-метода.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Storages\ReportsStorage;

$container = (new DiContainerBuilder())
    ->load('/app/config/storage_service.php')//[!code highlight]
    ->build();

$storage = $container->get(ReportsStorage::class);
var_dump($storage->getPermission());
// int(384)
```

```php [storage_service.php]
<?php
declare(strict_types=1);

// file: /app/config/storage_service.php//[!code highlight]

use App\Storages\ReportsStorage;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(ReportsStorage::class)
        ->bindArguments(
            dir: '/var/data'
        )
        ->setup(//[!code ++]
            'setPermission',//[!code ++]
            [0600]//[!code ++]
        );//[!code ++]
};
```

```php [ReportStorage.php]
<?php
declare(strict_types=1);

// file: /app/src/Storages/ReportStorage.php//[!code highlight]

namespace App\Storages;

final class ReportStorage
{
    private int $permission = 0777;

    public function __construct(
        public readonly string $dir
    ) {}

    public function setPermission(int $permission): void
    {
        $this->permission = $permission;
    }

    public function getPermission(): int
    {
        return $this->permission;
    }
}
```

:::

> [!NOTE]
> [Пример использования метода `setup()`](../../cookbook/autowire-setup.md#diautowire-setup)

### setupImmutable()

Метод `setupImmutable()` внедряет зависимость в PHP класс через **иммутабельный сеттер-метод**[^SetterMethodImmutable], и описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`.

Сеттер-метод должен возвращать тип `self`, `static` или тип объявляемого PHP класса в хелпер функции `diAutowire()`.

Сигнатура метода:

```php 
DiDefinitionSetupAutowireInterface::setupImmutable(
    string $method,
    array $arguments = []
)
``` 
Параметры:
- `$method` – имя сеттер-метода в классе.
- `$arguments` – аргументы к параметрам сеттер-метода.

> [!NOTE]
> [Пример использования метода `setupImmutable()`](../../cookbook/autowire-setup.md)

## bindTag()
Теги позволяют отнести объявляемый PHP класс к коллекции элементов отмеченных одним тегом.

<!--@include: _include/bind_tag.md-->

## setResetter()

<!--@include: _include/set-resetter.md-->

## Идентификатор контейнера для diAutowire() { #container-id }

Хелпер функция `diAutowier()` может передавать контейнеру идентификатор [^ContainerId] из параметра `$definition`, что обеспечивает доступ к нему через методы контейнера `get()` и `has()`.

[Файл конфигураций](../container-builder/configuration_files.md) с автоматическим формированием идентификатора контейнера:

```php
<?php
declare(strict_types=1);

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    /**
     * Доступ через метод контейнера// [!code highlight]
     * $container->get(PDO::class)// [!code highlight]
     */
    yield diAutowire(PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn')
        ),
    );
    /**
     * ℹ️ Конфигурирование выше эквивалентно записи // [!code highlight]
     * yield PDO::class => diAutowire(PDO::class) // [!code highlight]
     */
};
```

Идентификатор контейнера для объявляемого PHP класса может быть указан произвольной строкой.

```php
<?php
declare(strict_types=1);

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    /**
     * Доступ через метод контейнера// [!code highlight]
     * $container->get('pdo-in-tmp-file')// [!code highlight]
     */
    yield diAutowire(PDO::class)//[!code --]
    yield 'pdo-in-tmp-file' => diAutowire(PDO::class)//[!code ++]
        ->bindArguments(
            dsn: diParameter('db.dsn_file')
        );

    /**
     * Доступ через метод контейнера// [!code highlight]
     * $container->get('pdo-in-memory')// [!code highlight]
     */
    yield 'pdo-in-memory' => diAutowire(\PDO::class)//[!code ++]
        ->bindArguments(
            dsn: diParameter('db.dsn_memory')
        );
};
```

<!--@include: ../_include/term_notes.md-->
