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

Для передачи неполного списка аргументов можно использовать именованные аргументы.

::: code-group

```php
// file: /app/src/container_build.php
require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\ReportMaker;

$container = (new DiContainerBuilder())
    ->load('/app/config/report_services.php')
    ->build();

$report = $container->get(ReportMaker::class);
var_dump($report->lastDays);
// int(10)
```
<<< @/_code/config/report_services.php#snippet{1}

<<< @/_code/src/Services/ReportMaker.php#snippet{1}

<<< @/_code/src/Storages/ReportStorage.php#snippet{1}

:::

## Сеттер-методы внедрения зависимостей { #di-autowire-setter-method }

Помимо внедрения зависимостей через конструктор PHP класса существует возможность внедрения зависимостей через сеттер методы PHP класса.

> [!TIP]
> - Аргументы передавать не нужно если у сеттер-метода нет параметров или они могут быть внедрены автоматически на основе конфигурации.
> - Для внедрения скалярных типов зависимостей рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».
> - Аргументы сеттер-метода могут принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.


### setup()

Метод внедряет зависимости в PHP класс через сеттер-методы и его следует **применять к мутабельным сеттер-методам PHP класса** [^SetterMethodMutable].

Метод `setup()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`.

Сигнатура метода:

```php 
DiDefinitionSetupAutowireInterface::setup(
    string $method,
    array $arguments = []
)
``` 
Параметры:
- `$method` – имя вызываемого метода в классе.
- `$arguments` – аргументы к параметрам метода класса.

> [!TIP]
Для неполного списка аргументов в параметре `$arguments` в качестве ключа в массиве нужно указать имя параметра сеттер-метода.

::: code-group

```php
// file: /app/src/container_build.php
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
// file: /app/config/storage_service.php
use Generator;
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
// file: /app/src/Storages/ReportStorage.php
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

Метод позволяет внедрять зависимости в PHP класс через сеттер-методы и его следует применять к иммутабельным сеттер-методам PHP класса [^SetterMethodImmutable].

Типизированное значение сеттер-метода должно быть `self`, `static` или того же класса, что и сам PHP класс.

Метод `setupImmutable()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`.

Сигнатура метода:

```php 
DiDefinitionSetupAutowireInterface::setupImmutable(
    string $method,
    array $arguments = []
)
``` 
Параметры:
- `$method` – имя вызываемого метода в классе.
- `$arguments` – аргументы к параметрам метода класса.

> [!NOTE]
> [Пример использования метода `setupImmutable()`](../../cookbook/autowire-setup.md)

## bindTag()
Теги позволяют отнести конфигурируемый PHP класс к коллекции элементов отмеченных одним тегом.

<!--@include: _include/bind_tag.md-->

## setResetter()

<!--@include: _include/set-resetter.md-->

## Идентификатор контейнера для diAutowire() { #container-id }

При объявлении PHP класса идентификатор контейнера [^ContainerId] может быть сформирован на основе FQCN [^FQCN].

Файл конфигураций с автоматическим формированием идентификатора контейнера:

```php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    // $container->get(\PDO::class)//[!code warning]
    yield diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn')
        ),
    );
    /**
     * ℹ️ Конфигурирование выше эквивалентно записи
     * yield \PDO::class => diAutowire(\PDO::class)
     */
};
```

Идентификатор контейнера для объявляемого PHP класса может быть указан произвольной строкой.

```php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    // $container->get('pdo-in-tmp-file')//[!code warning]
    yield diAutowire(\PDO::class)//[!code --]
    yield 'pdo-in-tmp-file' => diAutowire(\PDO::class)//[!code ++]
        ->bindArguments(
            dsn: diParameter('db.dsn_file')
        );

    // $container->get('pdo-in-memory')//[!code warning]
    yield 'pdo-in-memory' => diAutowire(\PDO::class)//[!code ++]
        ->bindArguments(
            dsn: diParameter('db.dsn_memory')
        );
};
```

<!--@include: ../_include/term_notes.md-->
