---
outline: [2, 4]
---
# diAutowire

## Обзор { #overview }

Хелпер функция создает объект на основе PHP класса с автоматическим внедрением зависимостей.

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

Функция `diAutowire` возвращает объект предоставляющий методы:
 - `bindArguments()` – передать аргументы для конструктора класса.
 - `setup()` – вызов сеттер метода класса с параметрами (_mutable setter method_) для настройки класса.
 - `setupImmutable()` – вызов сеттер метода класса с параметрами (_immutable setter method_) и возвращаемым значением.
 - `bindTag()` – добавляет тег с мета-данными для определения.
 - `setResetter()` - установить конфигурацию для сброса состояния объекта.

## bindArguments()

Метод передачи аргументов для конструктора PHP класса
описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface`

Сигнатура метода:

```php
DiDefinitionArgumentsInterface::bindArguments(
    mixed ...$argument
)
```

Параметры:
- `$argument` – аргументы к параметрам конструктора класса.

> [!TIP]
> ✅ Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](../09-container-parameters.md)».

> [!WARNING]
> Метод `bindArguments()` перезаписывает ранее добавленные аргументы.

Для указания неполного списка аргументов можно использовать именованные аргументы параметров:

```php 
use App\Services\Bar;
use function \Kaspi\DiContainer\diAutowire;

diAutowire(Bar::class)
    ->bindArguments(name: 'Lorem ipsum');
```
::: code-group

```php [src/Services/Bar.php]
namespace App\Services;

final class Bar {
    public function __construct(
        private Foo $foo,
        string $name,
    ) {} 
}
```
```php [src/Services/Foo.php]
namespace App\Services;

final class Foo {
    public function __construct() {} 
}
```
:::

> [!TIP]
> Для параметров не объявленных через метод `bindArgument()` контейнер попытается разрешить зависимости самостоятельно.

> [!TIP]
> Параметр `$argument` в методе `bindArgument()` может принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.
>

## Сеттер-методы внедрения зависимостей { #di-autowire-setter-method }

Помимо внедрения зависимостей через конструктор PHP класса существует возможность внедрения зависимостей через сеттер методы PHP класса.

> [!TIP]
> - Для аргументов не объявленных для сеттер-метода контейнер по попытается разрешить зависимости автоматически на основе конфигурации.
> - Для указания как разрешать скалярные типы зависимостей рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».
> - Аргументы передаваемые в сеттер-метод могут принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.


### setup()

Внедрение зависимостей через вызов метода PHP класса без учёта возвращаемого значения метода (mutable setters).

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

Если в сеттер-методе нет параметров или они могут быть разрешены автоматически, то аргументы передавать не нужно:

```php
use App\Services\Bar;
use function \Kaspi\DiContainer\diAutowire;

diAutowire(Bar::class)
       ->bindArguments(name: 'Lorem ipsum')
       ->setup('doSetup');
```

::: code-group

```php [src/Services/Bar.php]
namespace App\Services;

final class Bar {
    public function __construct(
        private Foo $foo,
        string $name,
    ) {}
    
    public function doSetup(Baz $baz) {}
}
```

```php [src/Services/Foo.php]
namespace App\Services;

final class Foo {
    public function __construct() {} 
}
```

```php [src/Services/Baz.php]
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

```php [src/Services/Bar.php]
namespace App\Services;

final class Bar {
    public function __construct() {}
    
    public function doSetupWithName(Foo $foo, ServiceXInterface $service) {}
}
```

```php [src/Services/Foo.php]
namespace App\Services;

final class Foo {
    public function __construct() {}
}
```

```php [src/Services/Baz.php]
namespace App\Services;

final class Baz implements ServiceXInterface {
    public function __construct() {}
}
```

:::


> [!NOTE]
> [Пример использования метода `setup()`](../../cookbook/php-definition/di-autowire-setup.md#diautowire-setup)

### setupImmutable()

Внедрение зависимостей через вызов метода PHP класса возвращающего измененное значение экземпляра PHP класса (mutable setters).

Типизированное значение сеттер-метода должно быть `self`, `static` или того же класса, что и сам PHP класс.
Контейнер вернет экземпляр класса созданного через вызываемый метод.


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
> [Пример использования метода `setupImmutable()`](../../cookbook/php-definition/di-autowire-setup.md)

## bindTag()
Теги позволяют отнести конфигурируемый PHP класс к коллекции сервисов.

Метод `bindTag()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`.

Сигнатура метода:

```php
DiDefinitionTagArgumentInterface::bindTag(
    string $name,
    array $options = [],
    null|int|string $priority = null
)
```

> [!TIP]
> Более подробное [описание работы с тегами](../05-tags.md).

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

## Идентификатор контейнера для diAutowire { #documentation_php-definition_di-autowire-id }

При конфигурировании идентификатор контейнера может быть сформирован на основе FQCN  (**Fully Qualified Class Name**).

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
