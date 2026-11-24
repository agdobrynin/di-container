The `bindArguments()` method for defining parameters is described by the `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface` interface.

Method signature:

```php
DiDefinitionArgumentsInterface::bindArguments(
    mixed ...$argument
)
```

Parameters:
- `$argument` – arguments.

When specifying how a dependency should be injected into a particular parameter using `bindArguments()`, you can use [named arguments](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments).

If the method does not explicitly specify how a dependency should be injected into a given parameter, the container will handle the injection automatically based on its configuration.

> [!TIP]
> - The `$argument` parameter in `bindArguments()` can accept helper functions such as `diGet()`, `diValue()`, `diAutowire()`, and others.
> - For injecting scalar-type dependencies, it is recommended to use [container parameters](/en/documentation/container-parameters/index.md).

> [!WARNING]
> The `bindArguments()` method overwrites any previously added arguments.
