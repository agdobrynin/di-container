---
outline: [2,4]
title: Helper function diGet()
---
### Helper function diGet()

The helper function `diGet()` injects a dependency via a container identifier [^ContainerId]; this is equivalent to calling the container's `get()` method.

In cases where a parameter of a method or a callable type[^CallableType] is specified using user-defined types (such as PHP classes, built-in PHP classes, or enums), there is no need to use the `diGet()` helper function; the container automatically injects the dependency based on the configuration.

The `diGet()` helper function should be used for dependency injection when the container cannot automatically determine the type of dependency. For example, if a method parameter is defined with the `mixed` type or as a composite type (such as an intersection or union of class types), the helper function serves to clarify exactly which value from the container should be injected into that parameter.

Function signature:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diGet(
    string $containerIdentifier
): DiDefinitionNoArgumentsInterface
```

Parameters:
- `$containerIdentifier` – container identifier [^ContainerId].

The helper function has no additional methods.

### Example of injection into a parameter of type mixed { #example }

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(public readonly mixed $entry) {}
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

:::

It is necessary to inject the PHP class `\App\Services\Foo` into the `$entry` parameter of the PHP class `\App\Services\Bar`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code ++]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->entry instanceof Bar);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Service\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diGet};


return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            entry: diGet(Bar::class)// [!code ++]
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
(bool) true
```

### Cookbook { #cookbook }

The “Cookbook” section provides examples of how to refine an ad by incorporating parameters:
- [Dependency injection of a PHP class that implements a specific interface](../../cookbook/inject-interface.md#helper-di-get).
- [Dependency injection for union type parameters](../../cookbook/inject-union-type-params.md#php-definition).

<!--@include: ../_include/term_notes.md-->
