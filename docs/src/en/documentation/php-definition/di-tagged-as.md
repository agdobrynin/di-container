---
aside: false
title: Helper function diTaggedAs()
---
### Helper function diTaggedAs()

The `diTaggedAs()` helper function injects a collection of items from the container that are marked with a specific tag.

Function signature:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diTaggedAs(
    string $tag,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
): DiDefinitionNoArgumentsInterface
```

Parameters:
- `$name` – the tag name or the FQCN [^FQCN] of a PHP interface.
- `$isLazy` – enables a "lazy collection."
- `$priorityDefaultMethod` – retrieves the priority from a default method within the PHP class.
- `$useKeys` – uses string identifiers for collection elements.
- `$key` – retrieves the identifier for a collection element based on a metadata name from the tag.
- `$keyDefaultMethod` – retrieves the identifier for a collection element from a default method within the PHP class.
- `$containerIdExclude` – excludes elements from the collection based on their container ID.
- `$selfExclude` – excludes the calling PHP class from the collection.

The helper function has no additional methods.

<!--@include: ../_include/need_import_php_class.md-->

> [!TIP]
> See the documentation section "[Defining a tag in configuration files](../tags/php-definition-tag.md)."
>
> For more information on how **collection item priorities** and **collection item identifiers** are specified, please refer to the documentation section "[Working with tags in the container](../tags/index.md)."


<!--@include: ../_include/term_notes.md-->
