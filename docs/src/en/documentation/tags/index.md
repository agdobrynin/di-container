---
outline: [2,4]
title: Tags
---
### Tags { #title }

Tags make it possible to expand the ways in which collections can be passed as parameters to methods and functions. Elements from a container that are marked with a tag of the same name are gathered into a collection.

Dependency injection is applicable to parameters of the following type:
- `iterable`
    - `\Traversable`
        - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` (_the argument `false` must be passed for the `$isLazy` parameter_)
- `\ArrayAccess & \Iterator & \Psr\Container\ContainerInterface`

Any definition within a container can be marked with one or more tags. Each tag can contain [metadata](tag-metadata.md).

Объявить теги определения контейнера можно используя:
- Хелпер функции:  [`diAutowire()`](../php-definition/di-autowire.md#bindtag), [`diCallable()`](../php-definition/di-callable.md#bindtag), [`diProxyClosure()`](../php-definition/di-proxy-closure.md#bindtag), [`diValue()`](../php-definition/di-value.md#bindtag).
- Атрибуты: [`Autowire`](../attribute-definition/autowire.md#tags), [`Tag`](../attribute-definition/tag.md).

Dependency injection of a collection of elements into a parameter can be declared using:
- Helper functions: [`diTaggedAs()`](../php-definition/di-tagged-as.md).
- Attributes: [`TaggedAs`](../attribute-definition/tagged-as.md).

<!--@include: ../_include/need_import_php_class.md-->

### Lazy Collection { #collection-is-lazy }

An element of a lazy collection is initialized only at the moment it is actually accessed; this allows the creation of an object's dependencies to be deferred until the point they are truly needed, rather than any earlier.

A "lazy collection" is defined via the `$isLazy` parameter in the [`diTaggedAs()` helper function](../php-definition/di-tagged-as.md) or the [`TaggedAs` attribute](../attribute-definition/tagged-as.md).

> [!WARNING]
> A lazy collection cannot be applied to a parameter of type `array`. For parameters of type `array`, the `$isLazy` parameter must be set to `false`.

### Identifier of an item in a collection { #collection-key }

By default, an injected collection uses the container ID[^ContainerId] of each element as its identifier.

This behavior can be modified using the `$useKeys`, `$key`, and `$keyDefaultMethod` parameters — available via the [`diTaggedAs()` helper function](../php-definition/di-tagged-as.md) or the [`TaggedAs` attribute](../attribute-definition/tagged-as.md).

Element identifiers within a collection can be arbitrary strings or integers.

For more information, see the documentation section on ["Collection Element Identifiers"](using-collecton-key/index.md).

### Section Contents { #toc }

- 🐘 [Declaring a tag in configuration files](php-definition-tag.md).
- #️⃣  [Declaring a tag via an attribute](attribute-definition-tag.md).
- 📑 [PHP interface as a tag name](interface-as-tag.md).
- 🚫 [Removing elements from a collection](exclide-from-collection.md).
- ⬆️ [Priority of collection elements](collection-priority/index.md).
- 🔑 [Collection element identifiers](using-collecton-key/index.md).

<!--@include: ../_include/term_notes.md-->
