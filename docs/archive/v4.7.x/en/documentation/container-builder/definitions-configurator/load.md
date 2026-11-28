---
outline: [2,4]
title: Declaring definitions from configuration files
---
### Declaring definitions from configuration files { #title }

In [configuration files](../configuration_files.md), it is possible to declare definitions from other configuration files.

```php
<?php
declare(strict_types=1);

// file: /app/config/services/mix_services.php // [!code highlight]

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->load('/app/config/config_one.php');// [!code ++]
    
    if ('STAGES' === getenv('APP_ENV') {
        $configurator->loadOverride('/app/config/services_for_stages.php');// [!code ++]
    }
};
```

### load()

The `load()` method registers definitions from configuration files while tracking the uniqueness of container identifiers [^ContainerId].

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::load(
    string $file,
    string ...$_
): void
```

Parameters:

- `$file` – the full path to the definitions configuration file.
- `$_` – the full path to the definitions configuration file.

### loadOverride()

The `loadOverride()` method registers definitions from configuration files, with the ability to override previously registered definitions that share the same container identifier [^ContainerId].

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface::loadOverride(
    string $file,
    string ...$_
): void
```

Parameters:

- `$file` – the full path to the definitions configuration file.
- `$_` – the full path to the definitions configuration file.

<!--@include: ../../_include/term_notes.md-->
