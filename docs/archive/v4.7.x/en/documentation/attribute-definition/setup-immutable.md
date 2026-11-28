---
outline: [2,4]
title: SetupImmutable attribute
---
### АSetupImmutable attribute

The `\Kaspi\DiContainer\Attributes\SetupImmutable` attribute injects dependencies into a PHP class via **immutable setter methods** [^SetterMethodImmutable].

The return value of such a setter method must be `self`, `static`, or the same PHP class to which the setter method belongs.

Attribute configuration:
- `\Attribute::TARGET_METHOD`
- `\Attribute::IS_REPEATABLE`

Attribute signature:

```php
\Kaspi\DiContainer\Attributes\SetupImmutable::__construct(
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
