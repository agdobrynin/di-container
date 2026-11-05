---
outline: [2, 4]
---
#  Объявление объекта в рантайм контейнере { #title }

## Обзор { #overview }

В некоторых сценариях использования контейнера может возникнуть ситуация когда необходимо внедрить зависимость PHP-класс, но конфигурация этого PHP-класса заранее не известна, т.е. на момент конфигурирования известен только идентификатор контейнера[^ContainerId] или FQCN[^FQCN] этого PHP-класса.

Реальное состояние объекта (_PHP-класса_) инициализируется вне контейнера, и устанавливается в контейнер только во время выполнения, по заранее определенному идентификатору контейнера или FQCN этого PHP-класса.

Такой PHP-класс в рамках контейнера зависимостей называется **«Runtime Definition»**.

Во время [компиляции контейнера](../container-builder/index.md#compile-to-file) параметры зависящие от **«Runtime Definition»** должны знать о существовании PHP-класса который будет установлен в контейнер зависимостей только во время выполнения.

Объявление в контейнере «**Runtime Definition**» через [хелпер функцию `diRuntime()`](../php-definition/di-runtime.md) или [атрибут `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

> [!IMPORTANT]
> Объявление в конфигурации контейнера **«Runtime Definition»** не создает конфигурацию PHP-класса, а только предоставляет идентификатор контейнера[^ContainerId].

## Пример внедрения «Runtime definition» { #example-di-runtime }

PHP-классы:

::: code-group

```php [Kernel.php]
<?php
declare(strict_types=1);

// file: /app/src/Core/Kernel.php //[!code highlight]

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
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php //[!code highlight]

namespace App\Services;

final class Qux
{
    public function __construct(
        private readonly string $secureString
    ) {}

    // …    
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use App\Core\Kernel;

final class Bar
{
    public function __construct(
        public readonly Kernel $kernel,
        public readonly string $baz,
    ) {}    
}
```

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly Qux $qux,
        public readonly string $baz,
    ) {}    
}
```

:::

Реальное состояние PHP-классов `\App\Core\Kernel` и `\App\Services\Qux` может быть получено только вне рамок конфигурации контейнера, но в проекте есть PHP-классы которые внедряют эти классы как зависимости конструктора – `\App\Services\Bar` и `\App\Services\Foo`.

**При сборке необходима компиляция контейнера**, поэтому необходимо объявить в контейнере зависимостей класс `\App\Core\Kernel` и класс `\App\Services\Qux` как **«Runtime Definition»**.

### Объявление в файлах конфигураций { #example-di-runtime-php-definition }

В файле конфигураций `runtime_services.php` объявляет PHP-классы `\App\Core\Kernel` и `\App\Services\Qux` через [хелпер функцию `diRuntime()`](../php-definition/di-runtime.md).

Объявим PHP-классы `\App\Services\Bar`, `\App\Services\Foo` и их скалярные зависимости в файле конфигураций `services.php`, контейнер самостоятельно внедрит типизированную зависимость[^TypeHints] на основании конфигурации контейнера.

Для объявления скалярных зависимостей используем [параметры контейнера](../container-parameters/inject_params.md).

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Bar, Foo};

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params.php')//[!code highlight]
    ->load('/app/config/runtime_services.php')// [!code ++]
    ->load('/app/config/services.php')// [!code ++]
    ->compileToFile('/app/var', 'App\Core\Container')//[!code ++]
    ->build();


// …

/*
 * Метод \App\Core\Kernel::initKernel()
 * установит реальные значения для Runtime Definition.
 */ 
$kernel = new \App\Core\Kernel($container);
$kernel->initKernel();

// …

$bar = $container->get(Bar::class);
var_dump($bar->kernel, $bar->baz);//[!code warning]

echo PHP_EOL;

$foo = $container->get(Foo::class);
var_dump($qux->qux, $foo->baz);//[!code warning]
```

```php [runtime_services.php]
<?php
declare(strict_types=1);

// file: /app/config/runtime_services.php // [!code highlight]

use App\Core\Kernel;
use App\Services\Qux;

use function Kaspi\DiContainer\diRuntime;

return static function (): Generator {
    yield diRuntime(Kernel::class);
    
    yield diRuntime(Qux::class);
};
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            baz: diParameter('app.baz_value')
        );
    
    yield diAutowire(Bar::class)
        ->bindArguments(
            baz: diParameter('app.baz_value')
        );
};
```

```php [params.php]
<?php
declare(strict_types=1);

// file: /app/config/params.php // [!code highlight]


return static function (): Generator {
    yield 'app.baz_value' => 'Lorem ipsum';
}
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
object(App\Core\Kernel)#2 (0) {
}
string(11) "Lorem ipsum"

object(App\Services\Qux)#4 (0) {
}
string(11) "Lorem ipsum"
```

### Объявление через атрибуты { #example-di-runtime-attribute }

PHP-классы `\App\Core\Kernel` и `\App\Services\Qux` объявить через [атрибут `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

Объявим скалярные зависимости в PHP-классах `\App\Services\Bar`, `\App\Services\Foo` через атрибут `\Kaspi\DiContainer\Attributes\Parameter`, типизированную зависимость[^TypeHints] в этих классах контейнер самостоятельно на основании конфигурации контейнера.

PHP-классы:

::: code-group

```php [Kernel.php]
<?php
declare(strict_types=1);

// file: /app/src/Core/Kernel.php //[!code highlight]

namespace App\Core;

use Kaspi\DiContainer\Attributes\DiRuntime;// [!code ++]
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use App\Services\Qux;

#[DiRuntime]// [!code ++]
class Kernel
{
// …
```

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;// [!code ++]

#[DiRuntime]// [!code ++]
final class Qux
{
// …
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use App\Core\Kernel;
use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]

final class Bar
{
    public function __construct(
        public readonly Kernel $kernel,
        #[Parameter('app.baz_value')]//[!code ++]
        public readonly string $baz,
    ) {}    
}
```

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]

final class Foo
{
    public function __construct(
        public readonly Qux $qux,
        #[Parameter('app.baz_value')]//[!code ++]
        public readonly string $baz,
    ) {}    
}
```
:::

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Bar, Foo};

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params.php')//[!code highlight]
    ->import(namespace: 'App\\', src: '/app/src')// [!code ++]
    ->compileToFile('/app/var', 'App\Core\Container')//[!code ++]
    ->build();

// …

/*
 * Метод \App\Core\Kernel::initKernel()
 * установит реальные значения для Runtime Definition.
 */ 
$kernel = new \App\Core\Kernel($container);
$kernel->initKernel();

// …

$bar = $container->get(Bar::class);
var_dump($bar->kernel, $bar->baz);//[!code warning]

echo PHP_EOL;

$foo = $container->get(Foo::class);
var_dump($qux->qux, $foo->baz);//[!code warning]
```

```php [params.php]
<?php
declare(strict_types=1);

// file: /app/config/params.php // [!code highlight]


return static function (): Generator {
    yield 'app.baz_value' => 'Lorem ipsum';
}
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
object(App\Core\Kernel)#2 (0) {
}
string(11) "Lorem ipsum"

object(App\Services\Qux)#4 (0) {
}
string(11) "Lorem ipsum"
```

<!--@include: ../_include/term_notes.md-->