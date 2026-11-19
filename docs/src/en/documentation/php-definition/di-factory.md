---
outline: [2,4]
title: Helper function diFactory()
---
### Helper function diFactory()

The helper function `di Factory()` injects a container dependency using the factory pattern[^FactoryPattern].

Function signature:

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

\Kaspi\DiContainer\diFactory(
    string|array $definition,
    ?bool $isSingleton = null
): DiDefinitionArgumentsInterface
```

Parameters:
- `$definition` – a representation of a PHP class and a factory method.
- `$isSingleton` – The container will return the same object instance (singleton pattern). If set to `null`, the value will be determined based on the [container configuration](../container-config/index.md#is-singleton-service-default).

The `diFactory()` function returns an object that provides the following methods:
- `bindArguments()` – to pass arguments to the factory method.

> [!TIP]
> Reads the documentation section [“Using factory methods for dependency injection”](../factories/index.md).

### bindArguments()

Specifying how a factory method should inject dependencies into specific method parameters.

<!--@include: ./_include/bind_arguments.md-->

### Container identifier for a factory method { #container-id }

To make the result of a factory method available via the container's `get()` and `has()` methods, you must specify the corresponding container identifier[^ContainerId] in the configuration file and declare the factory.

#### Example of declaring a container identifier for a factory { #example }

PHP classes:

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(private readonly Bar $bar) {}
    
    public function createValue(string $prefixValue): string
    {
        return $prefixValue.' '.$this->bar->doSomething();
    }
}

```

```php [Bar.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

final class Bar
{
    // …

    public function doSomething(): string
    {
        return 'dolor sit amet';
    }
}
```
:::

The container method `get('services.foo_factory')` should return the result of executing the factory `\App\Services\Foo::createValue()`.

For the factory the container will automatically inject the dependency into the `$bar` constructor parameter, based on the configuration.

For the `\App\Services\Foo::createValue()` method, configure the injection of a scalar type into the `$prefixValue` parameter using the `bindArguments()` method.

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/factories.php')// [!code highlight]
    ->build();

$result = $container->get('services.foo_factory');// [!code highlight]

var_dump($result);
```

```php [factories.php]
<?php
declare(strict_types=1);

// file: /app/config/factories.php// [!code highlight]

use App\Services\Foo;

use function Kaspi\DiContainer\diFactory;

return static function(): Generator {
    yield 'services.foo_factory' => diFactory([Foo::class, 'createValue'])//[!code ++]
        ->bindArguments(//[!code ++]
           prefixValue: 'Lorem ipsum'//[!code ++]
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

<!--@include: ../_include/term_notes.md-->
