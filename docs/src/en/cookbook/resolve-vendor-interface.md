---
outline: [3, 5]
title: Dependency injection for PHP classes that implement third-party interfaces
---
### Dependency injection for PHP classes that implement third-party interfaces { #overview }

There are two ways to register an implementation of an interface from a third-party source, such as another PHP package:
1. Register the interface implementation globally within the container via a configuration file.
2. Inject a specific implementation of an interface into a particular parameter.

The choice of approach depends on the specific task at hand.

Below, we will look at how to configure the container for [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/) and the PHP package [Monolog](https://packagist.org/packages/monolog/monolog).

#### PHP classes { #php-classes-1 }

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/src/Services/Foo.php
<<< @/_code/cookbook/resolve-vendor-interface/src/Services/Bar.php
:::

The documentation provides an example of a logger configuration:

```php
<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('name');
$log->pushHandler(new StreamHandler('path/to/your.log', Level::Warning));

// add records to the log
$log->warning('Foo');
$log->error('Bar');
```

As the example shows, for a flexible logger configuration, we can define several arguments within the [container parameters](../documentation/container-parameters/index.md):
- `'name'` – the name of the logger channel
- `'path/to/your.log'` – the logger file
- `\Monolog\Level::Warning` – the logging level for messages

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/parameters.php
:::

To declare the implementation of an interface, you can use different options, for example, using an anonymous function and the helper function [`diCallable()`](../documentation/php-definition/di-callable.md), or a factory[^FactoryPattern] method of a PHP class and the helper function [`diFactory()`](../documentation/php-definition/di-factory.md). You can also define dependency injection for the parameters of an interface implementation's methods by using helper functions or attributes.

Let's select the helper function [`diAutowire()`](../documentation/php-definition/di-autowire.md) or [attribute `\Kaspi\DiContainer\Attributes\Autowire`](../documentation/attribute-definition/autowire.md) to declare the implementation of the interface `\Psr\Log\LoggerInterface`.

---

### Global declaration of interface implementation { #global-config }

Globally declaring that a specific PHP class implements an interface allows the container to automatically inject that class as a dependency whenever a method parameter or a callable[^CallableType] argument is type-hinted with `\Psr\Log\LoggerInterface`.

Important note: it is essential to ensure that the same object — the one implementing the interface — is injected each time, rather than creating a new instance for every dependency injection.

Since the `\Psr\Log\LoggerInterface` is provided by a third party, and we do not have direct access to its source code, we cannot use the `\Kaspi\DiContainer\Attributes\Service` attribute to declare the implementation.

To address this, we can define the interface implementation in the `services.php` configuration file using the `diAutowire()` helper function, where the container identifier[^ContainerId] is set to the FQCN[^FQCN] of the interface, mapped to the `\Monolog\Logger` PHP class.

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/container_build.php
<<< @/_code/cookbook/resolve-vendor-interface/services_en.php [services.php]
<<< @/_code/cookbook/resolve-vendor-interface/parameters.php
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
object(Monolog\Logger)#12 (0) {
}
object(Monolog\Logger)#12 (0) {
}
```

> [!NOTE]
> Note that the object identifier will be the same for all injected objects in this example, `#12`. This container behavior is due to the `\Monolog\Logger` PHP class being specified as the value for the `$isSingleton` parameter in the [`diAutowire()`](../documentation/php-definition/di-autowire.md) helper function.

---

### Injecting an interface implementation into a specific parameter { #param-config }

When defining an injection for a method parameter or a callable type[^CallableType], you can override the [globally configured interface implementation](#global-config) by specifying exactly which implementation should be injected into a particular parameter.

Suppose that, specifically for the PHP class `\App\Services\Bar`, you need to inject a logger with different parameters for instance, one that writes logs using the `Monolog\Handler\SocketHandler`.

The required logger configuration:
```php
<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\SocketHandler;

// create a log channel
$log = new Logger('app_bar_logger');
$handler = new SocketHandler('udp://remote-server:1234', Level::Debug);
$log->pushHandler($handler);

// add records to the log
$log->warning('Foo');
$log->error('Bar');
```

Let’s move the declaration for the PHP class `\Monolog\Handler\SocketHandler` into a separate configuration file, `services_monolog.php`.

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/services_monolog_en.php [services_monolog.php]
:::

We will use the injection declaration in the `$logger` constructor parameter of the PHP class `\App\Services\Bar` via the attribute [`\Kaspi\DiContainer\Attributes\Autowire`](../documentation/attribute-definition/autowire.md), since we can change the code of the PHP class itself.


::: code-group
```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/Services/Bar.php [!code highlight]

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]
use Monolog\Logger;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as Parameter;//[!code ++]
use Kaspi\DiContainer\Attributes\Setup;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;//[!code ++]

final class Bar
{
    public function __construct(
        #[Autowire(//[!code ++]
            Logger::class,//[!code ++]
            arguments: [new Parameter('baz_logger.name')],//[!code ++]
            setups: [//[!code ++]
                'pushHandler' => new Setup(//[!code ++]
                    // The reference to a PHP class object[!code warning]
                    new DiGet('baz_logger.handler')//[!code ++]
                ),//[!code ++]
            ],//[!code ++]
        )]//[!code ++]
        public readonly LoggerInterface $logger
    ) {}

    // …
}

```
:::

When building the container, let's add a new configuration file, `services_monolog.php`:

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__ . '/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->load('/app/config/services.php')//[!code highlight]
    ->loadParameters('/app/config/parameters.php')//[!code highlight]
    ->load('/app/config/services_monolog.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->logger->getHandlers());

$bar = $container->get(Bar::class);
var_dump($bar->logger->getHandlers());
```
<<< @/_code/cookbook/resolve-vendor-interface/services.php
<<< @/_code/cookbook/resolve-vendor-interface/parameters.php
<<< @/_code/cookbook/resolve-vendor-interface/services_monolog.php
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
array(1) {
    [0]=>
    object(Monolog\Handler\StreamHandler)#12 (0) {
    }
}
array(1) {
    [0]=>
    object(Monolog\Handler\SocketHandler)#23 (0) {
    }
}
```

<!--@include: ../documentation/_include/term_notes.md-->
