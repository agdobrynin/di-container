---
aside: false
title: Configuration of individual object state reset values
---
### Configuration of individual object state reset values { #title }

You can restrict the resetting of object states within the container by declaring the `\Kaspi\DiContainer\ObjectResetters` PHP class in your [configuration file](../container-builder/configuration_files.md), along with a collection of values that define how the states of the specified objects should be reset.

The collection used to specify these state resets consists of key-value pairs: the key is the container identifier[^ContainerId] used to retrieve the corresponding PHP class via the container's `get()` method while the value instructs the `\Kaspi\DiContainer\ObjectResetters` class on how to perform the state reset for that object (see [here](index.md#resseter-types) for details).

> [!IMPORTANT]
> When you manually declare the `\Kaspi\DiContainer\ObjectResetters` PHP class, object state resets will be performed only for the PHP classes explicitly included in your collection.

### Example of an ObjectResetters class declaration { #example }

PHP classes:

::: code-group
<<< @/_code/object-resetter/src/Services/Foo.php
<<< @/_code/object-resetter/src/Services/Bar.php
<<< @/_code/object-resetter/src/Helpers/Resetters.php
<<< @/_code/object-resetter/src/Services/Baz.php
<<< @/_code/object-resetter/src/Services/Qux.php
:::

You must define the values for resetting the state of objects specifically for the following PHP classes:
- `\App\Services\Bar`: Use an anonymous function that calls the methods `doResetPropertyFoo()` and `doResetPropertyBar()`, which reset the state of the class's two properties.
- `\App\Services\Qux`: Implement the `reset()` method as defined by the `\Kaspi\DiContainer\Interfaces\ResetInterface` interface.

In the `object_resetters.php` configuration file, define the PHP class `\Kaspi\DiContainer\ObjectResetters` using the `diAutowire()` helper function, ensuring that the `$isSingleton` parameter is set to `true`.

::: code-group
```php [app.php]
<?php
declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\ObjectResetters;//[!code ++]
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/config/object_resetters.php')//[!code ++]
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container;
);

$resetters = $container->get(ObjectResetters::class);//[!code ++]

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);

    $resetters->reset();//[!code ++]
}
```
```php [object_resetters.php]
<?php
declare(strict_types=1);

// file: /app/config/object_resetters.php [!code highlight]

use Kaspi\DiContainer\ObjectResetters;
use App\Services\{Bar, Qux};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    $resetters = [//[!code highlight]
        Bar::class => static function (Bar $bar): void {//[!code highlight]
            $bar->doResetPropertyFoo();//[!code highlight]
            $bar->doResetPropertyBar();//[!code highlight]
        },//[!code highlight]
        Qux::class => 'reset',//[!code highlight]
    ];//[!code highlight]

    yield diAutowire(ObjectResetters::class, isSingleton: true)//[!code ++]
        ->setup('setup', [$resetters]);//[!code ++]
};
```
:::

> [!IMPORTANT]
> Please note that the `$resetters` argument should be passed to the `setup()` method as an array, given that the method's `$arguments` parameter is defined as an `array` type.

<!--@include: ../_include/term_notes.md-->