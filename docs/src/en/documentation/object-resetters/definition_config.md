---
outline: [2,4]
title: Configuration for resetting object state based on container definitions
---
### Configuration for resetting object state based on container definitions { #title }

When registering a PHP class in the container via a helper function or an attribute, you can specify a value that instructs the container on exactly how the object's state should be reset.

The container has the built-in capability to automatically generate a configuration for resetting object states based on the values provided for each registered PHP class; there is no need to manually define the `\Kaspi\DiContainer\ObjectResetters` class or its configuration.

The `reset()` method, provided by the `\Kaspi\DiContainer\ObjectResetters` class, will perform the state reset for every registered PHP class that has an associated state-reset value.

When registering a PHP class within [configuration files](../container-builder/configuration_files.md), you must use the `setResetter()` method available via the following helper functions to specify the state-reset value:
- [`diAutowire()`](../php-definition/di-autowire.md#setresetter)
- [`diRuntime()`](../php-definition/di-runtime.md#setresetter)

When registering a PHP class using attributes, you must pass the state-reset value via the `$resetter` constructor parameter for the following attributes:
- [`\Kaspi\DiContainer\Attributes\Autowire`](../attribute-definition/autowire.md)
- [`\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md)

> [!TIP]
> For applications using **PHP 8.5 and higher**, the `$resetter` constructor parameter for an attribute can be defined as a [static anonymous function](https://www.php.net/manual/en/functions.anonymous.php#functions.anonymous):
> ```php
>  use Kaspi\DiContainer\Attributes\Autowire;
>  
>  #[Autowire(
>       isSingleton: true,
>       resetter: static function (Foo $foo): void {//[!code ++]
>           // implementation of an object-reset mechanism.
>       }//[!code ++]
>  )]
>  final class Foo
>  {
>    //
>  }
> ```


### Example of configuring an object state reset { #example }

PHP classes:

::: code-group
<<< @/_code/object-resetter/src/Services/Foo.php
<<< @/_code/object-resetter/src/Services/Bar.php
<<< @/_code/object-resetter/src/Helpers/Resetters.php
<<< @/_code/object-resetter/src/Services/Baz.php
<<< @/_code/object-resetter/src/Services/Qux.php
:::

You need to define how the state of objects in the container should be reset for the following PHP classes:
- `\App\Services\Foo`: via the class method `doReset()`.
- `\App\Services\Bar`: via an anonymous function that calls the methods `doResetPropertyFoo()` and `doResetPropertyBar()`, which reset the state of two properties within that class.
- `\App\Services\Baz`: via the callable `[App\Helpers\Resetters::class, 'resetObject']`.
- `\App\Services\Qux`: via the `reset()` method, as defined by the `\Kaspi\DiContainer\Interfaces\ResetInterface` interface.

> [!NOTE]
> For the PHP class `\App\Services\Qux`, it is not necessary to explicitly declare a value for resetting the object's state; the class itself implements the `\Kaspi\DiContainer\Interfaces\ResetInterface` interface. When configuring the `\Kaspi\DiContainer\ObjectResetters` component, the container will automatically verify that the class implements the required interface and include it in the configuration for resetting object states.

#### Declaration in configuration files { #example-php-definitin }

Let’s examine the declaration of PHP classes in the `services.php` configuration file, where object state resetting is configured using the `setResetter()` method with various valid values.

```php
<?php
declare(strict_types=1);

// file: /app/src/config/services.php [!code highlight]

use App\Services\{Foo, Bar, Baz, Qux};
use App\Helpers\Resetters;

use function Kaspi\DiContainer\{diAutowire, diRuntime};

return static function (): Generator {
    yield diAutowire(Foo::class, isSingleton: true)// [!code highlight]
        ->setResetter('doReset');//[!code ++]

    yield diAutowire(Bar::class, isSingleton: true)// [!code highlight]
        ->setResetter(static function (Bar $bar): void {//[!code ++]
            $bar->doResetPropertyFoo();//[!code ++]
            $bar->doResetPropertyBar();//[!code ++]
        });//[!code ++]

    yield diAutowire(Baz::class, isSingleton: true)// [!code highlight]
        ->setResetter([Resetters::class, 'resetObject']);//[!code ++]

    yield diRuntime(Qux::class, isSingleton: true);// [!code highlight]
};
```

#### Declaration via attributes { #example-attribute }

Let’s examine the declaration of PHP classes using attributes, where the resetting of object state is configured by passing various valid values to the `$resetter` constructor parameter.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(resetter: 'doReset')]//[!code ++]
final class Foo
{
//…
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]
use App\Helpers\Resetters;//[!code ++]

#[Autowire(resetter: [Resetters::class, 'resetObject'])]//[!code ++]
final class Baz
{
//…
```

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Attributes\DiRuntime;//[!code ++]

#[DiRuntime]//[!code ++]
final class Qux implements ResetInterface
{
//…
```
::: 

For the PHP class `\App\Services\Bar`, resetting the object's state requires calling two class methods: `doResetPropertyFoo()` and `doResetPropertyBar()`. **If using PHP 8.5 or higher**, the `$resetter` constructor parameter for the attribute can be defined as a static anonymous function:

::: code-group
```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(//[!code ++]
    resetter: static function (Bar $bar): void {//[!code ++]
        $bar->doResetPropertyFoo();//[!code ++]
        $bar->doResetPropertyBar();//[!code ++]
    },//[!code ++]
)]//[!code ++]
final class Bar
{
//…
```
:::

**For PHP versions below 8.5**, we can provide the `$resetter` constructor parameter as any callable type[^CallableType] for example, the function `\App\do_reset_bar()`.

::: code-group
```php [app_functions.php]
<?php
declare(strict_types=1);

// file: /app/functions/app_functions.php [!code highlight]

namespace App;

// …

use App\Services\Bar;//[!code ++]

if (!function_exists('App\do_reset_bar')) {//[!code ++]
    function do_reset_bar(Bar $bar): void {//[!code ++]
        $bar->doResetPropertyFoo();//[!code ++]
        $bar->doResetPropertyBar();//[!code ++]
    }//[!code ++]
}//[!code ++]
```
```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(resetter: 'App\do_reset_bar')]//[!code ++]
final class Bar
{
//…
```
:::

<!--@include: ../_include/term_notes.md-->