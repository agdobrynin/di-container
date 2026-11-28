---
outline: [2-4]
title: Defining container parameters
---
### Defining container parameters { #title }

Container parameters are declared using the `\Kaspi\DiContainer\DiContainerBuilder` class, or in configuration files via the [definitions configurator](../container-builder/definitions-configurator/index.md).

> [!IMPORTANT]
> <!--@include: _include/declare_order.md-->

Container parameter registration is typically defined in configuration files that represent key-value collections mapping parameter names to their respective values as PHP arrays. It is also possible to define parameters using any iterable collection; for instance, one can use a `callable` that returns an `iterable` type, allowing the collection to be traversed.

### Defining container parameters from files { #load-parameters }

```php
Kaspi\DiContainer\DiContainerBuilder::loadParameters(
    string $file,
    string ...$_
): static
```

Parameters:
- `$file` – the full path to the file that defines the parameter configuration.
- `$_` – additional configuration files for container parameters.

> [!IMPORTANT]
> The container parameter configuration file must begin with the `return` keyword and return either an iterable type or a `callable` type that, in turn, must also return an iterable type.

### Declaring container parameters from a collection { #add-parameters }

```php
Kaspi\DiContainer\DiContainerBuilder::addParameters(
    iterable $params
): static
```

Parameters:
- `$params` – a key-value collection of configuration parameters.

### Declaration of a single container parameter { #set-parameter }

```php
Kaspi\DiContainer\DiContainerBuilder::setParameter(
    string $name,
    array|int|float|string|bool|null|\UnitEnum $value
): static
```

Parameters:
- `$name` – the name of the parameter.
- `$value` – the value of the parameter.

### Declaring container parameters in the definition configurator { #cofigurator }

You can also declare and manage container parameters in configuration files using the [definitions configurator](../container-builder/definitions-configurator/index.md).

> [!IMPORTANT]
> Any parameters previously added to the configuration can be replaced with new values, provided the parameter name matches.

The `\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface` interface provides methods for declaring container parameters:

- `DefinitionsConfiguratorInterface::loadParameters()` – declaring container parameters from files.
- `DefinitionsConfiguratorInterface::addParameters()` – declaring container parameters from a collection.
- `DefinitionsConfiguratorInterface::setParameter()` – declaration of a parameter and its value.
- `DefinitionsConfiguratorInterface::removeParameter()` – removing a parameter by name from the container configuration.
- `DefinitionsConfiguratorInterface::hasParameter()` – checking for the availability of a parameter by name.

Example of declaring and managing container parameters in a configuration file:

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    $configurator->loadParameters('/app/config/more_app_params.php');

    if (!$configurator->hasParameter('debug')) {
        $configurator->setParameter('baz', null);
    } else {
        $configurator->removeParameter('foo');
    }

    yield diAutowire(Foo::class)
        ->bindArguments(
            diParameter('baz')
        );
};
```
