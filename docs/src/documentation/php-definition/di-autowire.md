---
outline: [2, 4]
---
# diAutowire

## Обзор { #overview }

Хелпер функция `diAutowire()` конфигурирует определение контейнера на основе PHP класса с автоматическим внедрением зависимостей.

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
- `$definition` – имя класса с пространством имен представленный строкой. Можно использовать безопасное объявление через магическую константу `::class` - `MyClass::class`
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$isLazy` – «ленивый объект». Подробнее в разделе – [Внедрение «ленивых» объектов контейнером](../14-lazy-injection.md).

Функция `diAutowire()` возвращает объект предоставляющий методы:
 - `bindArguments()` – аргументы конструктора PHP класса.
 - `setup()` – внедрение зависимостей через мутабельный сеттер-метод [^SetterMethodMutable].
 - `setupImmutable()` – внедрение зависимостей через иммутабельный сеттер-метод [^SetterMethodImmutable].
 - `bindTag()` – тег с мета-данными.
 - `setResetter()` - конфигурация для сброса состояния объекта.

## bindArguments()

Аргументы для конструктора PHP класса.

<!--@include: ./_include/bind_arguments.md-->

Для передачи неполного списка аргументов можно использовать именованные аргументы параметров:

```php 
use App\Services\Bar;
use function \Kaspi\DiContainer\diAutowire;

diAutowire(Bar::class)
    ->bindArguments(name: 'Lorem ipsum');
```
::: code-group

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

final class Bar {
    public function __construct(
        private Foo $foo,
        string $name,
    ) {} 
}
```
```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

final class Foo {
    public function __construct() {} 
}
```
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

```php
use App\Services\Bar;
use function \Kaspi\DiContainer\diAutowire;

diAutowire(Bar::class)
       ->bindArguments(name: 'Lorem ipsum')
       ->setup('doSetup');
```

::: code-group

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

final class Bar {
    public function __construct(
        private Foo $foo,
        string $name,
    ) {}
    
    public function doSetup(Baz $baz) {}
}
```

```php [Foo.php]
//file: /app/src/Services/Foo.php

namespace App\Services;

final class Foo {
    public function __construct() {} 
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php

namespace App\Services;

final class Baz {
    public function __construct() {} 
}
```

:::

Для неполного списка аргументов в параметре `$arguments` в качестве ключа в массиве нужно указать имя параметра сеттер-метода:

```php
use App\Services\{Bar, Baz};
use function \Kaspi\DiContainer\{diAutowire, diGet};

diAutowire(Bar::class)
    ->setup('doSetupWithName', ['service' => diGet(Baz::class)]);
```

::: code-group

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

final class Bar {
    public function __construct() {}
    
    public function doSetupWithName(Foo $foo, ServiceXInterface $service) {}
}
```

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

final class Foo {
    public function __construct() {}
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php

namespace App\Services;

final class Baz implements ServiceXInterface {
    public function __construct() {}
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
Теги позволяют отнести конфигурируемый PHP класс к коллекции сервисов.

<!--@include: ./_include/bind_tag.md-->

## setResetter()

Индивидуальная конфигурация для сброса состояния объекта.

Метод `bindTag()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface`.

```php
DiDefinitionResetterSetterInterface::setResetter(
    callable|false|string $resetter
)
```

> [!TIP]
> Более подробное [описание конфигурации для сброса состояния объекта](../12-object-resetters.md).

## Идентификатор контейнера для diAutowire { #container-id }

При конфигурировании идентификатор контейнера может быть сформирован на основе FQCN [^FQCN].

Конфигурирование с автоматическим формированием идентификатора контейнера:

```php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    // идентификатор контейнера сформируется
    // из имени класса, включая пространство имен
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

Идентификатор контейнера в [файлах конфигурации](../container-builder/configuration_files.md) может быть указан для
нужных определений контейнера:

```php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    // $container->get('pdo-in-tmp-file')
    yield 'pdo-in-tmp-file' => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn_file')
        );

    // $container->get('pdo-in-memory')
    yield 'pdo-in-memory' => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn_memory')
        );
};
```

<!--@include: ../_include/term_notes.md-->
