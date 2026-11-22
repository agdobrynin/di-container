---
outline: [2,4]
title: Declaring an object in a runtime container
---
###  Declaring an object in a runtime container { #title }

In certain container usage scenarios, a situation may arise where it is necessary to inject a PHP class as a dependency, yet the configuration for this class is not known in advance meaning that at the time of configuration, only the container identifier[^ContainerId] or the fully qualified class name.

The actual state of the object (the PHP class instance) is initialized outside the container and injected into the container only at runtime, using a pre-defined container identifier or the class's FQCN[^FQCN].

Within the context of a dependency container, such a PHP class is referred to as a **Runtime Definition**.

During [container compilation](../container-builder/methods/compile-to-file.md), parameters that depend on a **Runtime Definition** must be aware of the existence of a PHP class that will only be injected into the dependency container at runtime.

You can create a definition within the **Runtime Definition** container using the [`diRuntime()`](../php-definition/di-runtime.md) helper function or the [`\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md) attribute.

> [!IMPORTANT]
> Declaring an item within the **Runtime Definition** container configuration does not create a PHP class configuration; it merely provides a container identifier[^ContainerId].

### Example of implementing a runtime definition { #example-di-runtime }

PHP classes:

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

The actual state of the PHP classes `\App\Core\Kernel` and `\App\Services\Qux` can only be determined outside the scope of the container configuration; however, the project contains other PHP classes specifically `\App\Services\Bar` and `\App\Services\Foo` that inject these classes as constructor dependencies.

**Container compilation is required during the build process**, which necessitates declaring both `\App\Core\Kernel` and `\App\Services\Qux` within the dependency container as **Runtime Definitions**.

#### Declaration in configuration files { #example-di-runtime-php-definition }

In the `runtime_services.php` configuration file, the PHP classes `\App\Core\Kernel` and `\App\Services\Qux` are declared using the helper function [`diRuntime()`](../php-definition/di-runtime.md).

We will declare the PHP classes `\App\Services\Bar` and `\App\Services\Foo`, along with their scalar dependencies, in the `services.php` configuration file; the container will automatically inject the type-hinted dependencies[^TypeHints] based on its configuration.

To declare scalar dependencies, we use [container parameters](../container-parameters/inject_params.md).

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
 * The \App\Core\Kernel::initKernel() method
 * will set the actual values for the Runtime Definition.
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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
object(App\Core\Kernel)#2 (0) {
}
string(11) "Lorem ipsum"

object(App\Services\Qux)#4 (0) {
}
string(11) "Lorem ipsum"
```

#### Declaration via attributes { #example-di-runtime-attribute }

Declare PHP classes `\App\Core\Kernel` and `\App\Services\Qux` via the attribute [`\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

Declare scalar dependencies in the PHP classes `\App\Services\Bar` and `\App\Services\Foo` using the `\Kaspi\DiContainer\Attributes\Parameter` attribute; the container will automatically resolve typed dependencies[^TypeHints] in these classes based on its configuration.

PHP classes:

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

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
object(App\Core\Kernel)#2 (0) {
}
string(11) "Lorem ipsum"

object(App\Services\Qux)#4 (0) {
}
string(11) "Lorem ipsum"
```

<!--@include: ../_include/term_notes.md-->