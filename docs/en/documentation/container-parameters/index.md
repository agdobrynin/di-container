---
outline: [2-4]
title: Container parameters
---
### Container parameters

When declaring a PHP class or a callable type[^CallableType], you do not need to specify how the container should inject dependencies of **simple types**; instead, you can provide the values "as-is" using the `bindArguments()`[^BindArguments] method.

**Simple types** include `int`, `float`, `string`, `bool`, `null`, `\UnitEnum`, or an `array` consisting of these types.

Consider the following example: we need to inject dependencies specifically simple types into PHP classes by providing the dependency values "as-is" via the `bindArguments()` method.

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
        private readonly string $adminEmail
    ) {}

    //…
}
```

```php [Configurator.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Configurator.php //[!code highlight]

namespace App\Services;

final class Configurator
{
    public function __construct(private readonly Bar $bar) {}

    public static function __invoke(string $adminEmail): mixed
    {
        // …
    }
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

Declaration of simple parameter implementations in a configuration file:

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php  //[!code highlight]

use App\Services\{Foo, Configurator};

use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            // as-is value// [!code highlight]
            adminEmail: 'admin@example.com'// [!code highlight]
        );

    yield 'services.do_something' => diFactory(Configurator::class)
        ->bindArguments(
            // as-is value// [!code highlight]
            adminEmail: 'admin@example.com'// [!code highlight]
        );
};
```

As you can see in the `services.php` configuration file, the same value, `'admin@example.com'`, is used for injection.

This recurring value can be defined as a "**container parameter**" a reusable value that can be referenced when declaring injections into parameters.

**Container parameter configuration can be represented as a collection of key-value pairs, where the key is the string name of the parameter, and the value is one of the [supported types](#parameter-support-types).**

In the example above, the value used for a class method parameter can be defined as a container parameter:

```php
<?php
declare(strict_types=1);

// file: /app/config/parameters.php //[!code highlight]

return [
    'adminEmail' => 'admin@example.com',
];
```

The helper function [`diParameter()`](inject_params.md#helper-fn-di-parameter) injects a dependency from container parameters:

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php  //[!code highlight]

use App\Services\{Foo, Configurator};

use function Kaspi\DiContainer\{diAutowire, diFactory, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            adminEmail: 'admin@example.com'// [!code --]
            adminEmail: diParameter('adminEmail')// [!code ++]
        );
        
    yield 'services.do_something' => diFactory(Configurator::class)
        ->bindArguments(
            adminEmail: 'admin@example.com'// [!code --]
            adminEmail: diParameter('adminEmail')// [!code ++]
        );
};
```

To declare container parameters, use the [`DiContainerBuilder::loadParameters()`](declare_params.md) method:

```php
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')// [!code ++]
    ->load('/app/config/services.php')
    ->import('App\\', src: '/app/src/')
    ->build();
```

### Supported parameter value types for containers { #parameter-support-types }

The following simple value types can be set as container parameters:
- scalar PHP types: `int`, `float`, `string`, and `bool`
- the `null` type
- enumerated types that inherit from `\UnitEnum`
- arrays consisting of the aforementioned types
- PHP constants (defined via the [`\define()`](https://www.php.net/manual/en/function.define.php) function) that hold values of the aforementioned types
- values from public constants defined within PHP classes or interfaces, provided those constants are of the aforementioned types.

```php
<?php
declare(strict_types=1);

return [
    // string value
    'adminEmail' => 'admin@example.com',
    // Boolean type
    'services.enable_logging' => true,
    // numeric type - integer, floating-point number
    'logger.port' => 1_025,
    // null type
    'smtp.context' => null,
    // array
    'ui.locales' => [
        'ru' => 'ru_RU.utf8',
        'en' => 'en_US.utf8',
        'fi' => 'fi_FI.utf8',
    ],
    // a value set from a PHP constant defined via `\define()`
    'value.from_constat' => CONSTATN_NAME,
    // a value set via a public constant in a class
    'value.from_class_constat' => Foo::MAX_SIZE,
    // a value defined via enumerations that inherit from `\UnitEnum`
    'value.from_enumerator' => FooEnum::Viewed,
];
```

### Section Contents { #toc }

- [Declaration of container parameters](declare_params.md).
- [References to the names of other container parameters](expression_in_params.md).
- [Declaring dependency injection via container parameters](inject_params.md).
- [Runtime container parameters](inject_runtime_params.md).

<!--@include: ../php-definition/_include/bind_notes.md-->
<!--@include: ../_include/term_notes.md-->