---
aside: false
title: Helper function diParameter()
---
### Helper function diParameter()

The helper function `diParameter()` injects a dependency from the container's parameters[^Parameter].

Function signature:

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diParameter(
    string $name = ''
): DiDefinitionNoArgumentsInterface
```

Parameters:
- `$name` – container parameter name.

> [!NOTE]
> Read the section of the documentation dedicated to [container parameters](../container-parameters/inject_params.md).

<!--@include: ../_include/term_notes.md-->
