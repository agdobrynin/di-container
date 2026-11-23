---
outline: [2,4]
title: Definition Configurator
---
### Definition Configurator { #title } 

Through the **Definition Configurator** you can declare or delete container definitions, use other configuration files, register container parameters, and use context to build logic for declaring container definitions.

The configurator is passed as the first parameter to the anonymous function for [configuration files](../configuration_files.md).

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (
    DefinitionsConfiguratorInterface $configurator //[!code ++]
): Generator {
    // …
};
```

### Definition Configurator Methods { #methods }

The configurator implements the `\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface` interface and provides methods for declaring definitions, registering container parameters, using configuration files, and working with context within those configuration files.

[Defining and retrieving container definitions](get_remove.md):
- `getDefinitions()` – retrieves all declared definitions.
- `setDefinition()` – declares a container definition.
- `getDefinition()` – retrieves a definition by its container identifier.
- `findTaggedDefinitions()` – retrieves container definitions by tag.
- `removeDefinition()` – removes a declaration from the container.


[Loading definitions from configuration files](load.md):
- `load()` – loading container definitions from configuration files.
- `loadOverride()` – loading container definitions from configuration files with overrides.

[Managing container parameters](params.md):
- `loadParameters()` – register container parameters from files.
- `addParameters()` – register container parameters from a collection.
- `setParameter()` – register a container parameter.
- `removeParameter()` – remove a container parameter.
- `hasParameter()` – check for the existence of a container parameter.

[Context for configuration files](context.md):
- `getContext()` – retrieve the context.

