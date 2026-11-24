---
outline: [2,4]
title: Declaring dependency injection via a factory
---
### Declaring dependency injection via a factory { #title }

Injection into a parameter of a method or a callable type[^CallableType] can be declared via a PHP factory class.

<!--@include: _include/auto_inject_params.md-->

Injection into parameters can be declared:
- via the [`diFactory()`](../php-definition/di-factory.md) helper function in configuration files.
- via the [`\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md) attribute.

Important note: the factory method[^FactoryPattern] must return a result that is compatible with the parameter type[^TypeHints] of the method or function.


### Example of declaring dependency injection via a factory { #example }

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly Qux $qux
    ) {}    
}
```

<<< @/_code/attribute-definition/di-factory/Qux.php

<<< @/_code/attribute-definition/di-factory/Configurator.php

<<< @/_code/attribute-definition/di-factory/Bar.php

<<< @/_code/attribute-definition/di-factory/Baz.php

:::

For the PHP class `\App\Services\Foo`, it is necessary to declare a dependency injection into the `$qux` parameter via the factory `\App\Helpers\Configurator::quxDoConfig()`.

#### Declaration in configuration files { #helper-fn-di-factory }

In the `services.php` configuration file, we will register the PHP class `\App\Services\Foo` in the container and configure dependency injection for the constructor parameter `$qux` using the `diFactory()` helper function.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

var_dump($container->get(Foo::class)->qux->value);
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php  // [!code highlight]

use App\Service\Foo;
use App\Helpers\Configurator;

use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            qux: diFactory([Configurator::class, 'doConfigQux'])//[!code ++]        
        );
};
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(4) "Fuuz"
```

#### Declaration via an attribute { #attribute-di-factory }

Declare the injection of a dependency into the `$qux` constructor parameter of the PHP class `\App\Services\Foo` using the `\Kaspi\DiContainer\Attributes\DiFactory` attribute.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Helpers\Configurator;//[!code ++]

final class Foo
{
    public function __construct(
        #[DiFactory([Configurator::class, 'doConfigQux'])]//[!code ++]
        public readonly Qux $qux
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
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

var_dump($container->get(Foo::class)->qux->value);
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(4) "Fuuz"
```

<!--@include: ../_include/term_notes.md-->
