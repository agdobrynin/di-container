---
outline: [2,4]
title: Context for configuration files
---
### Context for configuration files { #title }

When building and configuring a container, you can define an additional context for configuration files.

This context is defined by the developer as a collection of key-value pairs. You can pass any type — such as an object, an array of values, or any other desired type — into the collection associated with a given key.

The methods [`DiContainerBuilder::addConfiguratorContexts()`](../methods/configuration-context.md#addconfiguratorcontexts) and [`DiContainerBuilder::setConfiguratorContext()`](../methods/configuration-context.md#setconfiguratorcontext) are used to set the context for configuration files.


### getContext()

The `getContext()` method retrieves a context value by name.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::getContext(
    string $name,
    ?callable $fallback = null
): mixed
```

Parameters:

Parameters:
- `$name` – the name of the context.
- `$fallback` – a fallback handler to be used in case the context is not found by its name.

> [!TIP]
> The `$fallback` parameter uses the type `callable(string $name): mixed`.
>
> If the `$fallback` parameter is set to `null` and the context is not found, an exception implementing the `\Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface` interface will be thrown.

### Example of using context in configuration files { #example-context }

In the configuration file `/app/app/config/foo_service.php`, a context is required to define a PHP class: a value must be provided for the `\App\Services\Foo::$dsn` property (for database configuration), and a value — computed via the `\App\Core::calculateFoo()` PHP method must be assigned to the `\App\Services\Foo::$foo` property.

Database connection settings are stored in the `DATABASE_DSN` environment variable.

Building the container:

::: code-group

```php [build_container.php]
<?php
declare(strict_types=1);

// file: /app/src/build_container.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use Dotenv\Dotenv;

$builder = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load(
        '/app/config/services/services.php',
        '/app/app/config/foo_service.php',// [!code highlight]
    );

// I am using a third-party library for working with `.env` files.
$dotenv = Dotenv::createImmutable('/app/');// [!code highlight]
$dotenv->required('DATABASE_DSN');// [!code highlight]
$dotenv->load();// [!code highlight]

$builder->setConfiguratorContext('DATABASE_DSN', $_ENV['DATABASE_DSN']);// [!code ++]

/**
 * an object containing the data needed to configure the container
 * @var \App\Core $core
 */ 
$builder->setConfiguratorContext($core::class, $core);// [!code ++]

// ...

$container = $builder->build();
```

```php [foo_service.php]
<?php
declare(strict_types=1);

// file: /app/app/config/foo_service.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use App\Core;
use App\Services\Foo;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {

    $dsn = $configurator->getContext('DATABASE_DSN');// [!code ++]
    /**
     * @var \App\Core $core
    */ 
    $core = $configurator->getContext(Core::class);// [!code ++]

    yield diAutowire(Foo::class)
        ->bindArguments(
            dsn: $dsn,
            foo: $core->calculateFoo(),
        );
};
```

:::

<!--@include: ../../_include/term_notes.md-->
