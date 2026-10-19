---
outline: [2, 4]
---
#  Конфигурирование объекта в рантайм контейнер { #title }

## Обзор { #overview }

В некоторых сценариях использования контейнера может возникнуть ситуация когда необходимо внедрить зависимость PHP класс, но конфигурация этого PHP класса заранее не известна, т.е. на момент конфигурирования известен только идентификатор контейнера[^ContainerId] или FQCN[^FQCN] этого PHP класса.

Реальное состояние объекта (_PHP класса_) инициализируется вне контейнера, и устанавливается в контейнер только во время выполнения, по заранее определенному идентификатору контейнера или FQCN этого PHP класса.

Такой PHP класс в рамках контейнера зависимостей называется **«Runtime Definition»**.

Во время [компиляции контейнера](../container-builder/index.md#compile-to-file) параметры зависящие от **«Runtime Definition»** должны знать о существовании PHP класса который будет установлен в контейнер зависимостей только во время выполнения.

Объявление в контейнере «**Runtime Definition**» через [хелпер функцию `diRuntime()`](../php-definition/di-runtime.md) или [атрибут `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

> [!IMPORTANT]
> Объявление в конфигурации контейнера **«Runtime Definition»** не создает конфигурацию PHP класса, а только предоставляет идентификатор контейнера[^ContainerId].

## Пример внедрения «Runtime definition» { #example-di-runtime }

Необходимо объявить в контейнере зависимостей класс `\App\Core\Kernel` и класс `\App\Services\Qux` как **«Runtime Definition»**.

PHP классы:

::: code-group

```php [Kernel.php]
// file: /app/src/Core/Kernel.php
namespace App\Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use App\Services\Qux;

class Kernel
{
    public function __construct(
        private readonly DiContainerInterface $container
    ) {}

    // ...

    public function initKernel(): void
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
    public function __construct(
        private readonly string $secureString
    ) {}

    // …    
}
```

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly Qux $qux
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use App\Core\Kernel;

final class Bar
{
    public function __construct(
        public readonly Kernel $kernel
    ) {}    
}
```

:::

### Файлы конфигураций { #example-di-runtime-php-definition }

Объявим в [файле конфигураций](../container-builder/configuration_files.md) требуемые нам PHP классы через [хелпер функцию `diRuntime()`](../php-definition/di-runtime.md):

::: code-group

```php [runtime_definitions.php]
// file: /app/config/runtime_definitions.php
use Generator;
use App\Core\Kernel;
use App\Services\Qux;

use function Kaspi\DiContainer\diRuntime;

return static function (): Generator {
    yield diRuntime(Kernel::class);
    
    yield diRuntime(Qux::class);
};
```

```php [services.php]
// file: /app/config/services.php
use Generator;
use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\diRuntime;

return static function (): Generator {
    yield diRuntime(Foo::class);
    
    yield diRuntime(Bar::class);
};
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Core\Kernel;
use App\Services\{Foo, Bar, Qux};

use function glob;

$container = (new DiContainerBuilder())
    ->load(...glob('/app/config/*.php'))
    ->compileToFile('/app/var', 'App\Core\Container')
    ->build();

/*
 * Метод \App\Core\Kernel::initKernel()
 * установит реальные значения для Runtime Definition.
 */ 
(new Kernel($container)->initKernel();

// …

$foo = $container->get(Foo::class);
var_dump($foo->qux instanceof Qux);
// (bool)true


$bar = $container->get(Bar::class);
var_dump($bar->kernel instanceof Kernel);
// (bool)true
```

### Атрибуты { #example-di-runtime-attribute }

Объявим требуемые нам PHP классы через [атрибут `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md): 


PHP классы:

::: code-group

```php [Kernel.php]
// file: /app/src/Core/Kernel.php
namespace App\Core;

use Kaspi\DiContainer\Attributes\DiRuntime;// [!code ++]
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use App\Services\Qux;

#[DiRuntime]// [!code ++]
class Kernel
{
    public function __construct(
        private readonly DiContainerInterface $container
    ) {}

    // ...

    public function initKernel(): void
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

use Kaspi\DiContainer\Attributes\DiRuntime;// [!code ++]

#[DiRuntime]// [!code ++]
final class Qux
{
    public function __construct(
        private readonly string $secureString
    ) {}

    // …    
}
```

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly Qux $qux
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use App\Core\Kernel;

final class Bar
{
    public function __construct(
        public readonly Kernel $kernel
    ) {}    
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Core\Kernel;
use App\Services\{Foo, Bar, Qux};

use function glob;// [!code --]

$container = (new DiContainerBuilder())
    ->load(...glob('/app/config/*.php'))// [!code --]
    ->import(namespace: 'App\\', src: '/app/src')// [!code ++]
    ->compileToFile('/app/var', 'App\Core\Container')
    ->build();

/*
 * Метод \App\Core\Kernel::initKernel()
 * установит реальные значения для Runtime Definition.
 */ 
(new Kernel($container)->initKernel();

// …

$foo = $container->get(Foo::class);
var_dump($foo->qux instanceof Qux);
// (bool)true


$bar = $container->get(Bar::class);
var_dump($bar->kernel instanceof Kernel);
// (bool)true
```
<!--@include: ../_include/term_notes.md-->