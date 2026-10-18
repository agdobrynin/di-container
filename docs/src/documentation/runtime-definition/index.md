---
outline: [2, 4]
---
#  Конфигурирование объекта в рантайм контейнер { #title }

## Обзор { #overview }

В некоторых сценариях использования контейнера зависимостей может возникнуть ситуация когда необходимо внедрить PHP класс как зависимость, но конфигурация этого PHP класса заранее не известна, т.е. на момент конфигурирования известен только идентификатор контейнера[^ContainerId] или FQCN[^FQCN] этого PHP класса.

Реальное состояние объекта (_PHP класса_) инициализируется вне контейнера, и устанавливается в контейнер зависимостей только во время выполнения, по заранее определенному идентификатору контейнера или FQCN этого PHP класса.

Такой PHP класс в рамках контейнера зависимостей называется **«Runtime Definition»**.

Объявление «**Runtime Definition**» необходимо для внедрения другими параметрами, зависящих от такого PHP класса. Во время [компиляции контейнера](../container-builder/index.md#compile-to-file) параметры должны знать о существовании PHP класса который будет установлен в контейнер зависимостей только во время выполнения.

Объявление в контейнере «**Runtime Definition**» через [хелпер функцию `diRuntime()`](../php-definition/di-runtime.md) или [атрибут `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

> [!IMPORTANT]
> Указание в конфигурации контейнера **«Runtime Definition»** не создает конфигурацию PHP класса, а только предоставляет идентификатор контейнера[^ContainerId].

## Пример внедрения «Runtime definition» { #example-di-runtime }

Необходимо добавить в контейнер зависимостей класс `\App\Core\Kernel` и класс `\App\Services\Qux` как **«Runtime Definition»**.

PHP классы:

::: code-group

```php [Kernel.php]
// file: /app/src/Core/Kernel.php
namespace App\Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use App\Services\Qux;

class Kernel
{
    public function __construct(private readonly DiContainerInterface $container) {}

    // ...

    protected function initKernel(): void
    {
        // ...

        $this->container->set($this::class, $this);
        
        $secureString = \calculate_secure_string();
        $qux = new Qux($secureString);
        
        $this->container->set($qux::class, $qux);
    }
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    public function __construct(private readonly string $secureString) {}

    // …    
}
```

```php
Foo
```
```php
Bar
```
:::

### Настройка в файлах конфигураций { #example-di-runtime-php-definition }

### Конфигурация через атрибуты { #example-di-runtime-attribute }

----
🚧🚧🚧🚧


## Пример использования хелпер функции `diRuntime` в конфигурационных файлах.

```php
// /app/config/runtime_definitions.php
use App\Core\Kernel;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diRuntime;

return static function (DefinitionsConfiguratorInterface $configurator) {
    yield diRuntime(Kernel::class);

    yield diRuntime('secure_string');
};
```
> [!NOTE]
> Другие определения в конфигурации могут «ссылаться»
> на идентификаторы контейнера `'App\\Core\\Kernel'` и `'secure_string'`
> - через [хелпер функцию `diGet()`](../03-php-definition.md#diget):
>   - `diGet(\App\Core\Kernel::class)`
>   - `diGet('secure_string')`
> - через [php атрибут `Inject`](../02-attribute-definition.md#inject):
>   - `#[Inject(\App\Core\Kernel::class)]`
>   - `#[Inject('secure_string')]`
> 

Конфигурация контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src/')
    ->load('/app/config/runtime_definitions.php')
    ->build()
;
```
Установка созданных экземпляров классов в «runtime definition»:
```php
namespace Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class Kernel {
    public function __construct(private DiContainerInterface $container) {}
    // ...
    protected function initKernel(): void
    {
        // ...

        $this->container->set($this::class, $this);
        
        $secureString = \calculate_secure_string();
        $this->container->set('secure_string', $secureString);
    }
}
```
## Пример использования PHP атрибута `DiRuntime` для конфигурирования.

```php
// /app/src/RuntimeServices/Foo.php
namespace App\RuntimeServices;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
final class Foo {
    // ...
}
```
```php
// /app/src/RuntimeServices/Bar.php
namespace App\RuntimeServices;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime('services.bar')]
final class Bar {
    // ...
}
```
Конфигурация контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src/')
    ->build()
;
```
Установка созданных экземпляров классов в «runtime definition»:
```php
namespace Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use App\RuntimeServices\{Foo, Bar};

class Kernel {
    public function __construct(private DiContainerInterface $container) {}
    // ...
    protected function initKernel(): void
    {
        // Инициализация класс Foo
        $foo = new Foo(...\do_calcualte_foo_params());
        $this->container->set(Foo::class, $foo);
        // Инициализация класс Bar
        $bar = new Bar(...\do_calcualte_bar_params());
        $this->container->set('services.bar', $bar);
    }
}
```

<!--@include: ../_include/term_notes.md-->