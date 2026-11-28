---
outline: [2,4]
title: PHP class declaration
---
### PHP class declaration { #title }

In cases where a PHP class cannot be instantiated simply by calling `new Foo()`, but instead requires a specific construction process, you can delegate the creation of that class to a dedicated PHP class, known as a “**factory**”.

<!--@include: _include/auto_inject_params.md-->

A PHP class can be defined via a factory in the following ways:
- using the [`diFactory()`](../php-definition/di-factory.md) helper function within configuration files.
- using the [`\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md) attribute.

Important note: the factory method[^FactoryPattern] must return the class being defined, thereby enabling access to that PHP class via the container's `get()` and `has()` methods.

### Example of declaring a PHP class via a factory { #di-factory-class-example }

PHP classes:

::: code-group

<<< @/_code/attribute-definition/di-factory/Qux.php

<<< @/_code/attribute-definition/di-factory/Configurator.php

<<< @/_code/attribute-definition/di-factory/Bar.php

<<< @/_code/attribute-definition/di-factory/Baz.php

:::

Register the PHP class `\App\Services\Qux` in the container using the factory `\App\Helpers\Configurator::quxDoConfig()`.

#### Declaration in configuration files { #example-helper-fn-di-factory }

In the `services.php` configuration file, we will register the PHP class `\App\Services\Qux` in the container using the `diFactory()` helper function.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Qux;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

var_dump($container->has(Qux::class));
var_dump($container->get(Qux::class)->value);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Services\Qux;

use function Kaspi\DiContainer\diFactory;

return static function (): Generator {
    yield Qux::class => diFactory([Configurator::class, 'doConfigQux']); //[!code ++]
};
```

::: 

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
(bool) true
string(4) "Fuuz"
```

#### Declaration via an attribute { #example-attribute-di-factory }

Register the PHP class `\App\Services\Qux` in the container using the `\Kaspi\DiContainer\Attributes\DiFactory` attribute.

::: code-group

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Helpers\Configurator;//[!code ++]

#[DiFactory([Configurator::class, 'doConfigQux'])]//[!code ++]
final class Qux
{
    public function __construct(public readonly string $value) {}
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
use App\Services\Qux;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

var_dump($container->has(Qux::class));
var_dump($container->get(Qux::class)->value);
```

:::


Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
(bool) true
string(4) "Fuuz"
```

<!--@include: ../_include/term_notes.md-->
