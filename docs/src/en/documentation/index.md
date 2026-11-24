---
outline: [2,4]
title: Manual. Introduction.
---
### Introduction { #documentation }

This documentation describes the container API from the [kaspi/di-container](https://packagist.org/packages/kaspi/di-container).

### PSR-11

The container is fully compatible with the [PSR-11](https://www.php-fig.org/psr/psr-11/) recommendations.

```php
<?php
declare(strict_types=1);

namespace Psr\Container;

interface ContainerInterface
{
    public function get($id);
 
    public function has($id);
}
```
### `call()`

The container implements the `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface` interface, which provides the call() method for callable types or values that can be converted by the container to the callable type.

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use Kaspi\DiContainer\DiContainerBuilder;

use function var_export;

$container = (new DiContainerBuilder())->build();

$container->call(
    static function(Foo $foo, string $name): void {
        $foo->doSomethind($name);
        sprintf('Call Foo::doSomething(%s)', var_export($name, true));
    },
    name: 'John'
);
// string(29) "Call Foo::doSomething('John')"
```

More details about the `call()` method are provided in the section [“Container method for callable types”](call/index.md).

### set()

The container implements the `\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface` interface, which provides the `set()` method for directly adding an object (an instantiated class) to an already assembled dependency container.

```php
<?php
declare(strict_types=1);

use App\Services\Bar;

$bar = new Bar(
    // set some dependencies.
);

// the identifier will be specified as 'App\Services\Bar'
$container->set($bar::class, $bar);
```

More details about the `set()` method are provided in the section [“Dynamically Adding Definitions to a Container”](container-builder/set.md).

### Implementation Features of Certain Classes and Interfaces { #resolve-class-interface }

When injecting dependencies of the types listed below, the current container will always be obtained:
- `\Psr\Container\ContainerInterface`
- `\Kaspi\DiContainer\Interfaces\DiContainerInterface`
- `\Kaspi\DiContainer\DiContainer`

Example:
```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

class Foo
{
    public function __construct(
        public readonly ContainerInterface $container//[!code warning]
    ) {}
}

$container = (new DiContainerBuilder())->build();

var_dump($container === $container->get(Foo::class)->container);
// (bool) true
```

### Dependency injection declaration { #using }

Dependency injection can be defined in [configuration files](php-definition/index.md) or via [attributes](attribute-definition/index.md); it is also possible to combine both methods of definition.


To build the container, use the [special `DiContainerBuilder` class](container-builder/index.md).

#### Contents of the documentation { #toc }

* 👷‍♂️ [Building the dependency container](container-builder/index.md).
* ⚙️ [Configuration for DiContainer](container-config/index.md).
* 🐘 [Declaration in configuration files](php-definition/index.md).
* #️⃣ [Declaration via attributes](attribute-definition/index.md).
* 📦 [The `call()` container method for callable types](call/index.md).
* 🔖 [Tags – labels for container definitions](tags/index.md).
* 📋 [Container parameters](container-parameters/index.md).
* 🗳️ [Declaring an object in a runtime container](runtime-definition/index.md).
* 🏭 [Using factory methods for dependency injection](factories/index.md).
* 🧹 [Dependency container reset](container-reset/index.md).
* ♻️ [Resetting object state for long-running processes](object-resetters/index.md).
* 💤 [Injection of lazy objects by the container](lazy-injection/index.md).

