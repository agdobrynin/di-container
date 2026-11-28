---
outline: [2,4]
title: Inject attribute
---
### Inject attribute

The `\Kaspi\DiContainer\Attributes\Inject` attribute injects a dependency via a container identifier [^ContainerId]; this is equivalent to calling the container's `get()` method.

In cases where a parameter of a method or a callable type[^CallableType] is defined using custom types (such as PHP classes, built-in PHP classes, or enumerations), the `Inject` attribute is not required — the container will automatically inject the dependency based on the configuration.

The `Inject` attribute is necessary in cases where the container cannot automatically determine the dependency type for a specific method parameter or a type being instantiated; in such situations, the type of the object to be injected must be specified explicitly. For example, if a method parameter is defined as `mixed` or as a composite type (an intersection or union types), this attribute allows you to precisely specify which value from the container should be injected into that parameter.

Attribute configuration:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\Inject::__construct(
    string $id = ''
)
```

Parameters:
- `$id` - container identifier [^ContainerId].

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
    public function __construct(
        public readonly mixed $entry
    ) {}
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

It is necessary to inject an object of the `\App\Services\Bar` class into the `$entry` constructor parameter of the `\App\Services\Foo` PHP class.

Since the `$entry` constructor parameter of the `\App\Services\Foo` class is typed as `mixed`, the container cannot automatically determine which type of dependency should be injected. We will specify the required object for injection using the `\Kaspi\DiContainer\Attributes\Inject` attribute.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Inject;// [!code ++]

final class Foo
{
    public function __construct(
        #[Inject(Bar::class)]// [!code ++]
        public readonly mixed $entry
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
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->entry instanceof Bar);
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

The "Recipes" section provides examples of how to refine an ad by incorporating parameters:
- [Dependency injection of a PHP class that implements a specific interface](../../cookbook/inject-interface/index.md#inject).
- [Dependency injection for union type parameters](../../cookbook/inject-union-type-params.md#php-attributes).

<!--@include: ../_include/term_notes.md-->
