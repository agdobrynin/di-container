---
aside: false
title: Parameter attribute
---
### Parameter attribute

The `\Kaspi\DiContainer\Attributes\Parameter` attribute injects a dependency from the container parameters.

Attribute configuration:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\Parameter::__construct(
    string $name = ''
)
```
Parameters:
- `$name` – the name of a container parameter.

> [!TIP]
> A more detailed description can be found in the [“Container Parameters”](../container-parameters/inject_params.md) section.
