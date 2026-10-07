# Setup

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Setup` конфигурирует внедрение зависимостей в PHP класс через сеттер-методы.
Этот атрибут следует **применять к мутабельным сеттер-методам PHP класса** [^SetterMethodMutable].

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Setup::__construct(
    mixed ...$argument
)
```

Параметры:
- `$argument` - аргументы для передачи в сеттер-метод.

## Аргументы сеттер-метода { #arguments }

Параметр `\Kaspi\DiContainer\Attributes\Setup:$argument` передает аргументы сеттер-методу.

<!--@include: _include/setter-method-args.md-->

## Приоритет { #priority }

Порядок вызова сеттер-метода будет определен порядком применения атрибута `\Kaspi\DiContainer\Attributes\Setup` к методу PHP-класса.

При необходимости можно изменить порядок вызова методов настройки класса [через атрибут `Kaspi\DiContainer\Attributes\SetupPriority`](setup-priority.md).

<!--@include: ../_include/term_notes.md-->
