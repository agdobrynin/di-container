# SetupImmutable

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable` внедряет зависимости в PHP класс через **иммутабельные сеттер-методам PHP класса** [^SetterMethodImmutable]. 

Возвращаемое значение сеттер-метода должно быть `self`, `static` или того же PHP класса к которому принадлежит сеттер-метод.

Конфигурация атрибута:
- `\Attribute::TARGET_METHOD`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\SetupImmutable::__construct(
    mixed ...$argument
)
```

Параметры:
- `$argument` - аргументы для передачи в сеттер-метод.

## Аргументы сеттер-метода { #arguments }

<!--@include: _include/setter-method-args.md-->

## Приоритет { #priority }

<!--@include: _include/setter-method-priority.md-->

<!--@include: ../_include/term_notes.md-->
