---
aside: false
title: Helper function diParameterRuntime()
---
### Helper function diParameterRuntime()

The helper function `diParameterRuntime()` injects a dependency from the runtime container parameters[^ParameterRuntime].

Function signature:

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diParameterRuntime(
    string $name = '',
    ?string $message = null
): DiDefinitionNoArgumentsInterface
```

Parameters:
- `$name` – the name of the container parameter.
- `$message` – an optional message to provide if the container parameter does not yet have a value.

> [!TIP]
> Read the [Runtime "Container Parameters"](../container-parameters/inject_runtime_params.md) section of the documentation.

<!--@include: ../_include/term_notes.md-->
