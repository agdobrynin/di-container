# diAutowire

Хелпер функция создает объект на основе PHP класса с автоматическим внедрением зависимостей.

Сигнатура функции:

```php
\Kaspi\DiContainer\diAutowire(
    string $definition,
    ?bool $isSingleton = null,
    bool $isLazy = false,
): \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface
    & \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface
    & \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface
```
Параметры:
- `$definition` – имя класса с пространством имен представленный строкой. Можно использовать безопасное объявление через магическую константу `::class` - `MyClass::class`
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](01-container-config.md).
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
> Метод перезаписывает ранее добавленные аргументы.

Для указания неполного списка аргументов можно использовать именованные аргументы параметров:

```php 
diAutowire(App\Services\Bar::class)
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

## setup()

Дополнительная настройка PHP класса через вызовы методов класса (mutable setters).

Метод `setup()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupAutowireInterface`.

Сигнатура метода:

```php 
DiDefinitionSetupAutowireInterface::setup(
    string $method,
    array $arguments = []
)
``` 
Параметры:
- `$method` – имя вызываемого метода в классе
- `$arguments` – аргументы к параметрам метода класса

Возвращаемое значение из вызываемого метода не учитывается при настройке сервиса,
контейнер вернет экземпляр класса созданного через конструктор класса.

> [!TIP]
> Для аргументов не объявленных через метод `setup()` контейнер по попытается разрешить зависимости автоматически на основе конфигурации.

> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».

> [!TIP]
> Аргументы передаваемые в метод `setup()` могут принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.

Если в сеттер методе нет параметров или они могут быть разрешены автоматически, то аргументы передавать не нужно:

```php
diAutowire(App\Services\Bar::class)
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

Для указания неполного списка аргументов сеттер метода в качестве ключа в массиве аргументов имя параметра сеттер метода:

```php
use function \Kaspi\DiContainer\diGet;

diAutowire(App\Services\Bar::class)
    ->bindArguments(name: 'Lorem ipsum')
    ->setup('doSetupWithName', ['service' => diGet(App\Services\Qux::class)]);
```

::: code-group

```php [src/Services/Bar.php]
namespace App\Services;

final class Bar {
    public function __construct(
        private Foo $foo,
        string $name,
    ) {}
    
    public function doSetupWithName(QuxInterface $qux) {}
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

```php [src/Services/Qux.php]
namespace App\Services;

final class  Qux {
    public function __construct() {}
}
```

:::


> [!NOTE]
> [Пример использования метода `diAutowire(...)->setup`](#пример-5)

**Дополнительная настройка сервиса через сеттер методы класса возвращающие значение (immutable setters):**
```php 
setupImmutable(string $method, array $arguments = [])
``` 
Параметры:
- `$method` – имя вызываемого метода в классе
- `$arguments` – аргументы к параметрам метода класса

Возвращаемое значение метода должно быть `self`, `static`
или того же класса, что и сам php класс.
Контейнер вернет экземпляр класса созданного через вызываемый метод.

> [!TIP]
> Для аргументов не объявленных через `setupImmutable()` контейнер по попытается разрешить зависимости автоматически на основе конфигурации.

> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в `$argument` рекомендуется использовать «[параметры контейнера](09-container-parameters.md)».

> [!TIP]
> Аргументы в `setupImmutable()` могут принимать хелпер функции такие как `diGet()`, `diValue()`, `diAutowire()` и другие.

> [!NOTE]
> [Пример использования метода `diAutowire(...)->setupImmutable`](#пример-6)
>
**Теги для определения:**
```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```

```php
  diAutowire(...)
      ->bindTag('tags.rules', priority: 100)
```
> [!TIP]
> Более подробное [описание работы с тегами](05-tags.md).

**Конфигурация сброса состояния объекта:**
```php
setResetter(callable|false|string $resetter)
```

> Более подробное [описание конфигурации для сброса состояния объекта](12-object-resetters.md).

##### Идентификатор контейнера для diAutowire.
При конфигурировании идентификатор контейнера может быть сформирован на основе FQCN  (**Fully Qualified Class Name**)

```php
// config/services_without_id.php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    // идентификатор контейнера сформируется
    // из имени класса включая пространство имен
    yield diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn')
        ),
    );
};
```
```php
// эквивалентно
// config/services_with_id.php
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {
    yeild \PDO::class => diAutowire(\PDO::class)
        ->bindArguments(
            dsn: diParameter('db.dsn')
        );
};
```
Если необходим другой идентификатор контейнера, то можно указывать так:
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
