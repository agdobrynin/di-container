---
aside: false
title: Configuration for DiContainer
---
### Configuration for DiContainer { #title }

The `\Kaspi\DiContainer\DiContainerConfig` class is used to configure the container.

The default settings provide an optimal configuration for the container's operation in most cases.

Constructor signature:

```php
\Kaspi\DiContainer\DiContainerConfig::__construct(
    useZeroConfigurationDefinition: true,
    useAttribute: true,
    isSingletonServiceDefault: false,
    isConfigureObjectResettersFromDefinitions: true,
)
```

Parameters:

* `$useZeroConfigurationDefinition`<span id="use-zero-configuration-definition"/> – zero-configuration dependency injection.
> You do not need to instruct the container on how to inject a specific PHP class if that class has no dependencies, depends only on other concrete classes, or depends on previously configured classes (or interfaces).

* `$isUseAttribute`<span id="use-attribute"/> – use attributes to declare definitions within the container.
> This parameter determines whether [PHP attributes](../attribute-definition/index.md) can be used for configuration.

* `$isSingletonServiceDefault`<span id="is-singleton-service-default"/> – whether to create a new object instance each time; defaults to `false`.
> For container definitions, you can specify how an object should be created: either by always returning the same instance (singleton) or by creating a new instance every time it is retrieved via the container's `get()` method.
> If no specific retrieval behavior is defined for a container entry, this default configuration value is applied.

* `$isConfigureObjectResettersFromDefinitions` – automatically configure the "object state resetter" service based on container definitions.
> In long-running processes, certain container services may require their state to be reset.
> An automated configuration mechanism can be used to handle this, based on the settings provided in the container definitions. For more details, see the section on ["Resetting Object State in Long-Running Processes"](../object-resetters/index.md).

The modified container configuration is passed via the `$containerConfig` parameter of the `\Kaspi\DiContainer\DiContainerBuilder` class constructor.

```php
use Kaspi\DiContainer\{DiContainerConfig, DiContainerBuilder};

$diConfig = new DiContainerConfig(
    useZeroConfigurationDefinition: false,
    useAttribute: false,
    isSingletonServiceDefault: true,
    isConfigureObjectResettersFromDefinitions: false,
);

// pass settings to the container builder
$container = (new DiContainerBuilder(containerConfig: $diConfig))
    ->build();
```
