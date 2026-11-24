---
aside: false
title: ParameterRuntime attribute
---
### ParameterRuntime attribute

The `\Kaspi\DiContainer\Attributes\ParameterRuntime` attribute will inject a dependency from a runtime container parameter[^ParameterRuntime].

Attribute configuration:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\ParameterRuntime::__construct(
    string $name = '',
    ?string $message = null
)
```

Parameters:
- `$name` – the name of a container parameter.
- `$message` – an optional message to provide if the container parameter does not yet have a value.

> [!TIP]
> More information can be found in the [Runtime container parameters](../container-parameters/inject_runtime_params.md) section.


<!--@include: ../_include/term_notes.md-->
