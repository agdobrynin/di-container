---
aside: false
title: Tag metadata
---
### Tag metadata { #title }

Tag metadata is specified as a key-value pair:
- **metadata name** – an arbitrary, non-empty string.
- **value** – a [scalar type](https://www.php.net/manual/en/language.types.type-system.php#language.types.type-system.atomic.scalar), `null`, or an `array` consisting of these types.

Tag metadata is passed via the `$options` parameter in the `bindTag()`[^BindTag] method
or within the [`\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) attribute.
<span id="reserved-keys"/>

> [!WARNING]
> In the metadata, the name `priority.method` is reserved for the **priority method**.
> ```php
> $options = [
>   'foo' => 'bar',
>   'baz' => true,
>   'priority.method' => 'getPriority'// [!code focus]
> ]
> ```
> More about [priority in the collection](collection-priority/priority-by-method.md).

<!--@include: ../php-definition/_include/bind_notes.md-->
