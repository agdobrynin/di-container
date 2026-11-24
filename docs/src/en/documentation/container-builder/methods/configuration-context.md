---
outline: [2,4]
title: Context for configuration files
---
### Context for configuration files { #title }
In [configuration files](../configuration_files.md), you can use a context to configure and build a container.

> [!TIP]
> A detailed description and usage examples can be found in the section "[Context for configuration files](../definitions-configurator/context.md)."

> [!WARNING]
> Values for previously added contexts may be overwritten if the context names match.

### addConfiguratorContexts()

The `addConfiguratorContexts()` method passes a collection of contexts for configuration files.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DiContainerBuilder::addConfiguratorContexts(
    iterable $contexts
): static
```
Parameters:
- `$contexts` – a key-value collection, where the key is the name of the context.

### setConfiguratorContext()

The `setConfiguratorContext()` method passes a named context for configuration files.

Method signature:

```php
\Kaspi\DiContainer\Interfaces\DiContainerBuilder::setConfiguratorContext(
    string $name,
    mixed $context
): static
```
Parameters:
- `$name` – the name of the context (a non-empty string).
- `$context` – the value of the context.

<!--@include: ../../_include/term_notes.md-->
