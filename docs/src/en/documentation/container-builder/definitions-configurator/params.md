---
outline: [2,4]
title: Managing container parameters
---
### Managing container parameters { #title }

Configuration files allow for the registration and management of [container parameters](../../container-parameters/index.md).

```php
<?php
declare(strict_types=1);

// file: /app/config/services/services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    $configurator->loadParameters('/app/config/parameters/app_params.php'); // [!code ++]
    $configurator->setParameter('params.baz', 'Lorem ipsum');// [!code ++]

    yield diAutowire(Foo::class)
        ->bindArguments(
            diParameter('params.foo')
        );
};
```

### loadParameters()

The `loadParameters()` method registers container parameters via parameter configuration files.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::loadParameters(
    string $file,
    string ...$_
): void
```

Parameters:

- `$file` – the full path to the file that defines the parameter configuration.
- `$_` – additional configuration files for container parameters.

### `addParameters()`

The `addParameters()` method registers container parameters from a collection.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::addParameters(
    iterable $params
): void
```

Parameters:
- `$params` – a key-value collection of configuration parameters.

### setParameter()

The `setParameter()` method registers a container parameter name and its value.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::setParameter(
    string $name,
    array|int|float|string|bool|null|\UnitEnum $value
): void
```

Parameters:
- `$name` – the name of the parameter.
- `$value` – the value of the parameter.

### removeParameter()

The `removeParameter()` method removes a container parameter from the configuration by its name.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeParameter(
    string $name
): void
```

Parameters:

- `$name` – parameter name.

### hasParameter()

The `hasParameter()` method checks for the existence of a specific container parameter in the configuration, based on the parameter name.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::removeParameter(
    string $name
): void
```

Parameters:

- `$name` – parameter name.

<!--@include: ../../_include/term_notes.md-->
