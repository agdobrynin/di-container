---
outline: [2,4]
title: DiFactory attribute
---
### DiFactory attribute

Declaration via the `\Kaspi\DiContainer\Attributes\DiFactory` attribute is implemented using the Factory pattern [^FactoryPattern]. This attribute registers a PHP class within the container or injects a dependency into a parameter of a method or a callable type [^CallableType].

Attribute configuration:
- `\Attribute::TARGET_CLASS`
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\DiFactory::__construct(
    string|array $definition,
    ?bool $isSingleton = null,
    array $arguments = []
)
```

Parameters:
- `$definition` – FQCN[^FQCN] and method name.
- `$isSingleton` – The container will return the same object instance (singleton pattern). If set to `null`, the value will be determined based on the [container configuration](../container-config/index.md#is-singleton-service-default).
- `$arguments` – arguments for the factory method.

> [!TIP]
> Read the documentation section [“Using factory methods for dependency injection”](../factories/index.md).

### Factory method arguments { #args }

The `$arguments` parameter of the `\Kaspi\DiContainer\Attributes\DiFactory` attribute specifies how dependencies should be injected into specific parameters of a PHP "factory" class method.

<!--@include: _include/arguments.md-->

<!--@include: _include/to_arguments.md-->


<!--@include: ../_include/term_notes.md-->
