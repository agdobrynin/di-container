# Setup

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Setup` внедряет зависимости в PHP класс через **мутабельные сеттер-методы**[^SetterMethodMutable].

Конфигурация атрибута:
- `\Attribute::TARGET_METHOD`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Setup::__construct(
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
