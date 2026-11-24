---
outline: [2,4]
title: Setup attribute
---
### Setup attribute

The `\Kaspi\DiContainer\Attributes\Setup` attribute injects dependencies into a PHP class via **mutable setter methods**[^SetterMethodMutable].

Attribute configuration:
- `\Attribute::TARGET_METHOD`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\Setup::__construct(
    mixed ...$argument
)
```

Parameters:
- `$argument` - arguments to be passed to a setter method.

### Setter method arguments { #arguments }

<!--@include: _include/setter-method-args.md-->

### Priority { #priority }

<!--@include: _include/setter-method-priority.md-->

<!--@include: ../_include/term_notes.md-->
