The `$arguments` parameter passes arguments to a setter method.

When defining dependency injection for a specific parameter, use the name of that parameter as the array key within `$arguments`.

Setter method parameters that do not receive explicit arguments will be injected automatically by the container, based on its configuration.

Arguments for a setter method can utilize helper functions such as `diGet()`, `diValue()`, `diAutowire()`, and others.

For the injection of scalar-type dependencies, it is recommended to use [container parameters](../../container-parameters/index.md).