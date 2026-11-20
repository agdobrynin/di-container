---
aside: false
title: Tag attribute
---
### Tag attribute

The `\Kaspi\DiContainer\Attributes\Tag` attribute declares a tag and metadata for a PHP class.

Attribute configuration:
- `\Attribute::TARGET_CLASS`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\Tag::__construct(
    string $name,
    array $options = [],
    int|null|string $priority = null,
    ?string $priorityMethod = null
)
```

Parameters:
- `$name` – the name of the tag.
- `$options` – the tag's metadata.
- `$priority` – the priority of the tag.
- `$priorityMethod` – the "priority method."

> [!TIP]
> A more detailed description can be found in the section [Tags](../tags/index.md).

<!--@include: ../_include/term_notes.md-->
