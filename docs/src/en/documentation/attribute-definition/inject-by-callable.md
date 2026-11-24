---
outline: [2,4]
title: InjectByCallable attribute
---
### InjectByCallable attribute

The `\Kaspi\DiContainer\Attributes\InjectByCallable` attribute injects a dependency by invoking the specified callable type[^CallableType].

Attribute configuration:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\InjectByCallable::__construct(
    callable $callable
)
```
Parameters:
- `$callable` – a callable type.

Parameters in the callable type can be automatically injected by the container, based on the container's configuration.

### Example of implementation via a callable type { #example }

PHP classes and functions:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

class Foo
{
    public function __construct(
        public readonly Bar $bar,
        public readonly string $calcStr,
    ) {}
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

final class Bar
{
    public function __construct(public readonly string $val) {}    
}
```

```php [Configurator.php]
<?php
declare(strict_types=1);

// file: /app/src/Helpers/Configurator.php // [!code highlight]

namespace App\Helpers;

use App\Services\Bar;

final class Configurator
{
    // …
    
    public static function doConfigBar(string $inputStr): Bar {
        $bar = new Bar($inputStr);

        // …
        
        return $bar;   
    }  
}
```

```php [functions.php]
<?php
declare(strict_types=1);

// file /app/src/functions.php // [!code highlight]

namespace App;

use function {bin2hex, random_bytes};

if (!function_exists('App\calculate_str')) {
    function calculate_str(): string {
        $str = bin2hex(random_bytes(5));
        
        // …
        
        return $str;
    }
}
```

:::

For the PHP class `\App\Services\Foo`, it is necessary to declare dependency injection for the constructor parameters `$bar` and `$calcStr`, both of which require complex calculations.

For the `$bar` parameter of the `\App\Services\Foo` class, it is necessary to use the `\App\Helpers\Configurator::doConfigBar()` method, which performs the calculations required to initialize the `\App\Services\Bar` PHP class. Since a static method of a PHP class is a callable type[^CallableType], we use the `\Kaspi\DiContainer\Attributes\InjectByCallable` attribute to inject the dependency into the `$bar` constructor parameter.

For the second parameter, `$calcStr`, of the `\App\Services\Foo` class, you need to inject a value calculated by the `\App\calculate_str()` function; this injection should be declared using the `\Kaspi\DiContainer\Attributes\InjectByCallable` attribute.

The `\App\Helpers\Configurator::doConfigBar()` method requires a scalar dependency for its `$inputStr` parameter; to declare this dependency injection, use the [`\Kaspi\DiContainer\Attributes\Parameter` attribute](parameter.md).

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\InjectByCallable;//[!code ++]
use App\Helpers\Configurator;//[!code ++]

class Foo
{
    public function __construct(
        #[InjectByCallable([Configurator::class, 'doConfigBar'])]//[!code ++]
        public readonly Bar $bar,
        #[InjectByCallable('App\calculate_str')]//[!code ++]
        public readonly string $calcStr,
    ) {}
}
```

```php [Configurator.php]
<?php
declare(strict_types=1);

// file: /app/src/Helpers/Configurator.php // [!code highlight]

namespace App\Helpers;

use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]
use App\Services\Bar;

final class Configurator
{
    // …
    
    public static function doConfigBar(
        #[Parameter('params.lorem')]//[!code ++]
        string $inputStr
    ): Bar {
        $bar = new Bar($inputStr);

        // …
        
        return $bar;   
    }  
}
```

:::

To inject scalar dependencies, the container requires a [container parameter](../container-parameters/index.md) named `'params.lorem'`; we will define this using the [`setParameter()` method](../container-builder/methods/load-parameters.md#setparameter).


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
    ->setParameter('params.lorem', 'Lorem ipsum')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->bar->val);
var_dump($foo->calcStr);
```

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string(11) "Lorem ipsum"
string(10) "121e767853"
```

<!--@include: ../_include/term_notes.md-->
