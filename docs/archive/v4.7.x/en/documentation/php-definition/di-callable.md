---
outline: [2,4]
title: Helper function diCallable()
---
### Helper function diCallable()

The helper function `diCallable()` injects a dependency by invoking the specified callable type[^CallableType].

Function signature:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\{
    DiDefinitionArgumentsInterface as Args,
    DiDefinitionTagArgumentInterface as Tag,
};

\Kaspi\DiContainer\diCallable(
    callable $definition,
    ?bool $isSingleton = null
): Args & Tag
```

Parameters:
- `$definition` – the callable type.
- `$isSingleton` – The container will return the same object instance (singleton pattern). If set to `null`, the value will be determined based on the [container configuration](../container-config/index.md#is-singleton-service-default).

The `diCallable()` function returns an object that provides the following methods:
- `bindArguments()` – specifies arguments for the function parameters.
- `bindTag()` – adds a tag containing metadata to the definition.

### bindArguments()

Passing arguments for the parameters of a callable type.

<!--@include: ./_include/bind_arguments.md-->

#### Example of dependency injection via the bindArguments() method { #example-bind-arguments }

PHP classes:

::: code-group
<<< @/_code/php-definition/di-callable/Baz.php

<<< @/_code/php-definition/di-callable/Bar.php

<<< @/_code/php-definition/di-callable/Foo.php
:::

In the `callable_entries.php` configuration file, you must declare the PHP class `\App\Services\Foo` and specify that a dependency should be injected into the `$value` parameter using a callable type[^CallableType] — specifically, `\App\Services\Baz::factoryMethod()`.

For the `\App\Services\Baz::factoryMethod()` method, you must configure the injection of a scalar value into the `$name` parameter using the `bindArguments()` method; the container will automatically inject the dependency for the `$bar` parameter based on the existing configuration.

::: code-group

<<< @/_code/php-definition/di-callable/container.php

<<< @/_code/php-definition/di-callable/callable_entries.php

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
string(26) "Lorem ipsum dolor sit amet"
```

### bindTag()

Tags allow a declared callable type to be associated with a collection of elements marked with the same tag.

<!--@include: _include/bind_tag.md-->

### Container identifier for the invoked type { #container-id }

To ensure that a callable type is accessible via the container’s `get()` and `has()` methods, you must specify the corresponding container identifier[^ContainerId] in the configuration file and declare the `callable` type.


#### Example of declaring an identifier for a callable type { #example-container-id }

PHP classes:

::: code-group

<<< @/_code/php-definition/di-callable/Baz.php

<<< @/_code/php-definition/di-callable/Bar.php

:::

The container method `$container->get('baz.factory_method')` should return the result of executing the callable `\App\Services\Baz::factoryMethod()`.

For the `\App\Services\Baz::factoryMethod()` method, configure the injection of a scalar value into the `$name` parameter using the `bindArguments()` method; the container will automatically inject the dependency for the `$bar` parameter based on its configuration.

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php// [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load('/app/config/factory.php')// [!code ++]
    ->build();

$result = $container->get('baz.factory_method');// [!code highlight]

var_dump($result);
```

```php [factory.php]
<?php
declare(strict_types=1);

// file: /app/config/factory.php // [!code highlight]

use App\Services\Baz;

use function Kaspi\DiContainer\diCallable;

return static function(): Generator {
    yield 'baz.factory_method' => diCallable([Baz::class, 'factoryMethod'])//[!code ++]
        ->bindArguments(//[!code ++]
            name: 'Lorem ipsum'//[!code ++]
    );//[!code ++]
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
string(26) "Lorem ipsum dolor sit amet"
```

### “As-is” declaration of a callable type { #convert-to-di-callable } 

In configuration files, you can specify a callable type[^CallableType] “as-is”, provided that the container is able to automatically inject the required dependencies based on its own configuration.

When a `callable` type is declared in a configuration file, the container converts it into a helper function called `diCallable()`.

#### Example of declaring callable types “as-is” { #example-convert-to-di-callable }

PHP classes and functions:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

use App\Helpers\Env;

use function getenv;

class Foo
{
    public function __construct(
        public readonly string $apiKey,
        public readonly bool $debug
    ) {}

    public static function makeForLocal(Env $env, bool $debug = true): self
    {
        $apiKey = $env::apiKey();

        return new self($apiKey, $debug);
    }
}
```

```php [functions.php]
<?php
declare(strict_types=1);

// file: /app/src/Functions/functions.php// [!code highlight]

namespace App\Functions;

use App\Services\Foo;
use App\Helpers\Env;

use function getenv;

function staging_service(Env $env, bool $debug = false): Foo {
    $apiKey = $env::apiKey();

    return new Foo($apiKey, $debug);
}

```

```php [Env.php]
<?php
declare(strict_types=1);

// file: /app/src/Helpers/Env.php// [!code highlight]

namespace App\Helpers;

use function getenv;

class Env
{
    // …

    public static function apiKey(): string
    {
        $apiKey = match (getenv('APP_ENV')) {
            'dev' => getenv('APP_API_KEY_LOCAL'),
            'staging' => getenv('APP_API_KEY_STAGING'),
            'prod' => getenv('APP_API_KEY_PROD'),
        };
        
        // $apiKey calculation
        
        return $apiKey;
    }
}
```

:::

In the `callable_as_is.php` configuration file, you need to declare three container identifiers:
- `'services.foo_from_anonymous_fn'` – an anonymous function.
- `'services.foo_from_static_method'` – a static class method.
- `'services.foo_from_function'` – a function.

When accessed using these identifiers, the container's `get()` method should return an instance of the PHP class `\App\Services\Foo` with different values for the `$apiKey` and `$debug` properties.

::: code-group 

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php// [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

putenv('APP_ENV=dev');// [!code highlight]
putenv('APP_API_KEY_LOCAL=FOO-BAR');// [!code highlight]
putenv('APP_API_KEY_STAGING=BAR-BAZ');// [!code highlight]
putenv('APP_API_KEY_PROD=BAZ-QUX');// [!code highlight]

$container = (new DiContainerBuilder())
    ->load('/app/config/callable_as_is.php')// [!code ++]
    ->build();

$fooFromFn = $container->get('services.foo_from_anonymous_fn');
var_dump($fooFromFn);

$fooFromStaticMethod = $container->get('services.foo_from_static_method');
var_dump($fooFromStaticMethod);

$fooFromFunction = $container->get('services.foo_from_function');
var_dump($fooFromStaticMethod);
```

```php [callable_as_is.php]
<?php
declare(strict_types=1);

// file: /app/config/callable_as_is.php// [!code highlight]

use App\Services\Foo;

return static function (): Generator {
   yield 'services.foo_from_anonymous_fn' => static fn () => new Foo(
        apiKey: 'qwerty-value',
        debug: false
    );

   yield 'services.foo_from_static_method' => [ServiceFoo::class, 'makeForLocal'];
   
   yield 'services.foo_from_function' => 'App\Functions\staging_service';
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
object(App\Services\Foo)#1 (2) {
  ["apiKey"]=>
  string(12) "qwerty-value"
  ["debug"]=>
  bool(false)
}

object(App\Services\Foo)#1 (2) {
  ["apiKey"]=>
  string(7) "FOO-BAR"
  ["debug"]=>
  bool(true)
}

object(App\Services\Foo)#1 (2) {
  ["apiKey"]=>
  string(7) "FOO-BAR"
  ["debug"]=>
  bool(false)
}
```


<!--@include: ../_include/term_notes.md-->
