---
outline: [2,4]
title: Service attribute
---
### Service attribute

The `\Kaspi\DiContainer\Attributes\Service` attribute tells the PHP interface its implementation.

Attribute configuration:
- `\Attribute::TARGET_CLASS`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\Service::__construct(
    string $id,
    ?bool $isSingleton = null
)
```

Parameters:
- `$id` – container identifier [^ContainerId].
- `$isSingleton` – The container will return the same object instance (singleton pattern). If set to `null`, the value will be determined based on the [container configuration](../container-config/index.md#is-singleton-service-default).

The `$id` parameter specifies the implementation of an interface via a PHP class, which is accessible within the container through the `get()` method.

Declaring an interface implementation using the `\Kaspi\DiContainer\Attributes\Service` attribute is global in scope; if no specific dependency injection instructions are provided for a given parameter, the container will resolve the dependency based on the interface configuration by retrieving the object from the container via the `get()` method, using the identifier specified in the `$id` parameter.

For example, specifying the string identifier `'services.qux'` for the `$id` parameter means that the container should inject the object obtained by calling the `$container->get('services.qux')` method.

### Example of interface configuration { #example-interface-service }

PHP classes and interfaces:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo
{
    public function __construct(public readonly QuxInterface $qux) {} 
}
```

```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php // [!code highlight]

namespace App\Interfaces;

interface QuxInterface
{
    // …
} 
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface
{
    // …
}
```

:::

It is necessary to declare globally that the PHP class `\App\Services\Bar` implements the interface `\App\Interfaces\QuxInterface`.

This means that, based on the configuration, the container will inject the PHP class `\App\Services\Bar` into any method or function parameters that are type-hinted with `\App\Interfaces\QuxInterface`.

::: code-group

```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php // [!code highlight]

namespace App\Interfaces;

use Kaspi\DiContainer\Attributes\Service;//[!code ++]
use App\Services\Bar;//[!code ++]

#[Service(Bar::class)]//[!code ++]
interface QuxInterface
{
    // …
} 
```

:::

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};
use App\Interfaces\QuxInterface;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->qux instanceof QuxInterface);
var_dump($foo->qux instanceof Bar);
```

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
bool(true)
bool(true)
```

### Configuration of third-party interfaces { #inject-vendor-interface }

When configuring dependency injection for a parameter typed as an interface—such as one provided by a third-party PHP package — you must specify the implementation of that interface via [configuration files](../container-builder/methods/load-definitions-from-file.md) or a [collection](../container-builder/methods/load-definitions-from-collection.md) during the container-building process.

```php
<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use App\Services\MyLogger;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    LoggerInterface::class => diAutowire(MyLogger::class);
};
```

An example showing how to implement a PHP class that satisfies a specific third-party interface can be found in the [Cookbook](../../cookbook/resolve-vendor-interface.md) section.

<!--@include: ../_include/term_notes.md-->
