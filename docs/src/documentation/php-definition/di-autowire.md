---
outline: [2, 4]
---
# diAutowire()

## Обзор { #overview }

Хелпер функция `diAutowire()` объявляет PHP-класс в контейнере.

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
- `$definition` – FQCN [^FQCN] объявляемого PHP-класса.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$isLazy` – объявить «ленивый объект».

> [!TIP]
> Изучите разделы документации:
> - [Внедрение «ленивых» объектов контейнером](../lazy-injection/index.md).
> - [Сброс состояния объектов](../object-resetters/index.md).

Функция `diAutowire()` возвращает объект предоставляющий методы:
 - `bindArguments()` – аргументы конструктора PHP-класса.
 - `setup()` – внедрение зависимостей через мутабельный сеттер-метод [^SetterMethodMutable].
 - `setupImmutable()` – внедрение зависимостей через иммутабельный сеттер-метод [^SetterMethodImmutable].
 - `bindTag()` – тег с метаданными.
 - `setResetter()` - значение для сброса состояния объекта.

## Идентификатор контейнера для diAutowire() { #container-id }

Хелпер функция `diAutowier()` может передавать контейнеру идентификатор [^ContainerId] из параметра `$definition`, что обеспечивает доступ к нему через методы контейнера `get()` и `has()`.

[Файл конфигураций](../container-builder/configuration_files.md) с автоматическим формированием идентификатора контейнера:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield Foo::class => diAutowire(Foo::class)//[!code --]
    yield diAutowire(Foo::class)//[!code ++]
};
```

Идентификатор контейнера для объявляемого PHP-класса может быть указан произвольной строкой.

```php
<?php
declare(strict_types=1);

use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    /**
     * $container->get('services.foo_one')// [!code highlight]
     */
    yield diAutowire(Foo::class);//[!code --]
    yield 'services.foo_one' => diAutowire(Foo::class);//[!code ++]

    /**
     * $container->get('services.foo_two')// [!code highlight]
     */
    yield 'services.foo_two' => diAutowire(Foo::class);//[!code ++]
};
```

## bindArguments()

Аргументы для конструктора PHP-класса.

<!--@include: _include/bind_arguments.md-->

### Пример внедрения зависимости через метод bindArguments() { #example-bind-arguments }

PHP-классы:

::: code-group

<<< @/_code/src/Services/ReportMaker.php

<<< @/_code/src/Storages/ReportStorage.php

:::

Необходимо через метод `bindArguments()` указать конструктору как внедрять зависимость в конкретный параметр:
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
    ->load('/app/config/report_services.php')//[!code ++]
    ->build();

$report = $container->get(ReportMaker::class);

var_dump($report->lastDays);
var_dump($report->storage->dir);
```

<<< @/_code/config/report_services.php

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
int(10)
string(9) "/var/data"
```

## Сеттер-методы внедрения зависимостей { #di-autowire-setter-method }

Помимо внедрения зависимостей через конструктор PHP-класса существует возможность внедрения зависимостей через сеттер-методы.

### setup()

Метод `setup()` внедряет зависимость в PHP-класс через **мутабельный сеттер-метод** [^SetterMethodMutable], и описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`.

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

<!--@include: _include/setter_method_arg.md-->

#### Пример внедрения через метод setup() { #example-setter-method-setup }

PHP-класс:

```php
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

Необходимо внедрить зависимость через сеттер-метод `\App\Storages\ReportsStorage::setPermission()`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Storages\ReportsStorage;

$container = (new DiContainerBuilder())
    ->load('/app/config/storage_service.php')//[!code ++]
    ->build();

$storage = $container->get(ReportsStorage::class);

var_dump($storage->getPermission());
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

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
int(384)
```

> [!NOTE]
> [Пример использования метода `setup()`](../../cookbook/autowire-setup.md#diautowire-setup)

### setupImmutable()

Метод `setupImmutable()` внедряет зависимость в PHP-класс через **иммутабельный сеттер-метод**[^SetterMethodImmutable], и описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`.

Сеттер-метод должен возвращать тип `self`, `static` или тип объявляемого PHP-класса в хелпер функции `diAutowire()`.

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

<!--@include: _include/setter_method_arg.md-->

> [!NOTE]
> [Пример использования метода `setupImmutable()`](../../cookbook/autowire-setup.md)

## bindTag()
Теги позволяют отнести объявляемый PHP-класс к коллекции элементов отмеченных одним тегом.

<!--@include: _include/bind_tag.md-->

## setResetter()

<!--@include: _include/set-resetter.md-->

<!--@include: ../_include/term_notes.md-->
