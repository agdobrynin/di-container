---
outline: [2,4]
title: Resetting object state for long-running processes
---
### Resetting object state for long-running processes { #title }

Depending on the configuration, some objects retrieved via the container's `get()` method may return [the same instance](../container-config/index.md#is-singleton-service-default) – meaning they retain their state across multiple calls to the container. In long-running processes, such objects may require their state to be reset.

An example of a class that can accumulate data:

<<< @/_code/object-resetter/src/Services/FooLogger.php

For example, an application might require an instance of the PHP class `\App\Services\FooLogger` to maintain its state across various system components meaning that the same object should be returned whenever it is injected as a dependency or retrieved via the container's `get()` method.

When registering such a PHP class in the container, you must set the `$isSingleton` parameter to `true` within the [`diAutowire()`](../php-definition/di-autowire.md) helper function or the [`\Kaspi\DiContainer\Attributes\Autowire`](../attribute-definition/autowire.md) attribute.

Example of registering a PHP class using an attribute:

```php
<?php
declare(strict_types=1);

// file: /app/src/Services/FooLogger.php [!code highlight]

namespace  App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(isSingleton: true)]//[!code ++]
final class FooLogger
{
//…
```

An application that operates based on an "event loop" model:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container
);

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);
}
```

When retrieving an object via `$container->get(App\Services\FooLogger::class)` within an application, the exact same object instance is returned. Various components of the application can inject this dependency and call the `\App\Services\FooLogger::log()` method, which will result in data accumulating within the `$logs` property of the `\App\Services\FooLogger` class.

**To prevent memory leaks, you must implement a mechanism to reset the state of the `\App\Services\FooLogger` object.**

The container provides a built-in mechanism for resetting the state of its objects via the `\Kaspi\DiContainer\ObjectResetters` class.

The configuration for resetting an object's state is defined as a key-value pair, where the key is the container identifier[^ContainerId], and the value, which can be a `string`, `false`, or a `callable`, specifies [how the object's state should be reset](#resseter-types).

In this case, we will use the public method `\App\Services\FooLogger::doReset()` to perform the state reset. Additionally, you must update the class definition by adding the `\Kaspi\DiContainer\Attributes\Autowire` attribute, specifying the name of the reset method – `'doReset'` – in the `$resetter` parameter.

```php
<?php
declare(strict_types=1);

namespace  App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(isSingleton: true)]//[!code --]
#[Autowire(//[!code ++]
    isSingleton: true,//[!code ++]
    resetter: 'doReset',//[!code ++]
)]//[!code ++]
final class FooLogger
{
//…

    public function doReset(): void//[!code ++]
    {//[!code ++]
        $this->logs = [];//[!code ++]
    }//[!code ++]
```

Now, let’s add the use of the PHP class `\Kaspi\DiContainer\ObjectResetters` to our application, which operates using an "event loop" pattern:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\ObjectResetters;//[!code ++]
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container
);

$resetters = $container->get(ObjectResetters::class);//[!code ++]

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);
    $resetters->reset();//[!code ++]
}
```

Now, the PHP class `\App\Services\FooLogger` will reset its state when `$resetters->reset()` is called.

### Methods for configuring the resetting of object state { #config }

You can declare the `\Kaspi\DiContainer\ObjectResetters` PHP class and its configuration for resetting object state in the following ways:

- [Configuration derived from container definitions](definition_config.md).
- [Configuration of individual object state reset values](manual_config.md).

> [!TIP]
> In cases where it is not possible to modify the code of a PHP class to implement a method for resetting the object's state, for example: when dealing with a PHP class from a third-party package, you can [use the object reflection mechanism](reset_vendor_class.md).

> [!NOTE]
> A developer can implement a custom PHP class for resetting object state, which must implement the `\Kaspi\DiContainer\Interfaces\ObjectResettersInterface` interface.

### Configuration values for resetting object state { #resseter-types }

The configuration for resetting an object's state is represented as a key-value pair, where the key is an identifier[^ContainerId] used to retrieve the object from the container via the `get()` method, and the value instructs the container on exactly how to reset the object's state.

An object's state can be reset via a **public method implemented within the same PHP class**; this method has access to all private and protected properties. The value in the configuration should specify the name of the method that is to perform the state reset.

For example, the configuration value `'doReset'` could be used for the PHP class `\App\Services\Foo` via the `\aspi\DiContainer\Attributes\Autowire` attribute, or for the PHP class `\App\Services\Bar` within a `services.php` configuration file.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(
    isSingleton: true,//[!code highlight]
    resetter: 'doReset',//[!code ++]
)]
final class Foo
{
    //…
    
    public function doReset(): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Bar;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class, isSingleton: true)//[!code highlight]
        ->setResetter('doReset');//[!code ++]
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

final class Bar
{
    //…
    
    public function doReset(): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}
```
:::

The object's state will be reset if the **PHP class implements the `\Kaspi\DiContainer\Interfaces\ResetInterface` interface** and its `reset()` method; note that it is not mandatory to specify a value for the object state reset in the attribute or helper function, as the default value is `false`.

::: code-group

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Interfaces\ResetInterface;//[!code ++]

final class Baz implements ResetInterface//[!code ++]
{
    //…
    
    public function reset(): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}
```
:::

Additionally, any callable type[^CallableType] can be used to reset an object's state. The callable receives the object whose state is being reset as its only argument: `callable(object $object): void`.

A callable can be a class with a static method, a regular function, or an anonymous function:

::: code-group

```php
$resetter1 = [App\Helpers\Resettrer::class, 'doResetFoo'];

$resetter2 = 'App\do_reset_bar';

$resetter3 = static function (object $object): void {
    // …
};
```

```php [Resettrer.php]
<?php
declare(strict_types=1);

namespace App\Helpers;

final class Resettrer
{
    /**
     * @param App\Services\Foo $object
     */
    public static function doResetFoo(object $object): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]

    // …
}
```

```php [app_functions.php]
<?php
declare(strict_types=1);

// file: /app/functions/app_functions.php [!code highlight]

namespace App;

if (!function_exists('App\do_reset_bar')) {
    /**
     * @param App\Services\Bar $object
     */
    function do_reset_bar(object $object): void {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}

```

:::

### Container configuration and resetting object state

In the `\Kaspi\DiContainer\DiContainerConfig` PHP class, which provides [configuration for the container](../container-config/index.md), the `$isConfigureObjectResettersFromDefinitions` parameter determines whether the container can automatically generate configuration for the `\Kaspi\DiContainer\ObjectResetters` class based on the container's definitions. By default, this parameter is set to `true`, which allows for the creation of a fully configured service for resetting the state of PHP classes registered within the container.

If you pass `false` as the value for the `$isConfigureObjectResettersFromDefinitions` parameter in `\Kaspi\DiContainer\DiContainerConfig`, the container will not automatically generate configuration for the `\Kaspi\DiContainer\ObjectResetters` class; however, you may still [create the configuration for this class manually](manual_config.md).

<!--@include: ../_include/term_notes.md-->
