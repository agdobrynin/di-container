---
aside: false
title: TaggedAs attribute
---
### TaggedAs attribute

The `\Kaspi\DiContainer\Attributes\TaggedAs` attribute injects a collection of items from the container that are marked with a tag of the same name.

Attribute configuration:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\TaggedAs::__construct(
    string $name,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
)
```

Parameters:
- `$name` – the tag name or the FQCN [^FQCN] of an interface.
- `$isLazy` – enables a "lazy collection."
- `$priorityDefaultMethod` – retrieves the priority from a default method within the PHP class.
- `$useKeys` – uses string identifiers for the collection elements.
- `$key` – retrieves the identifier for a collection element based on a metadata name from the tag.
- `$keyDefaultMethod` – retrieves the identifier for a collection element from a default method within the PHP class.
- `$containerIdExclude` – excludes elements from the collection based on their container ID.
- `$selfExclude` – excludes the calling PHP class from the collection.

<!--@include: ../_include/need_import_php_class.md-->

> [!TIP]
> Explore the sections of the documentation:
> - [Declaring a tag via an attribute](../tags/attribute-definition-tag.md).
> - [Tags](../tags/index.md).

<!--@include: ../_include/term_notes.md-->
