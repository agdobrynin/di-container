# SetupImmutable

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable` конфигурирует внедрение зависимостей в PHP класс через сеттер-методы.
Этот атрибут следует **применять к иммутабельным сеттер-методам PHP класса** [^SetterMethodImmutable].

Возвращаемое значение сеттер-метода должно быть `self`, `static` или того же PHP класса к которому принадлежит сеттер-метод.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\SetupImmutable::__construct(
    mixed ...$argument
)
```

Параметры:
- `$argument` - аргументы для передачи в сеттер-метод.

## Аргументы сеттер-метода { #arguments }

Параметр `\Kaspi\DiContainer\Attributes\SetupImmutable:$argument` передает аргументы сеттер-методу.

<!--@include: _include/setter-method-args.md-->

## Приоритет { #priority }

Порядок вызова сеттер-метода будет определен порядком применения атрибута `\Kaspi\DiContainer\Attributes\SetupImmutable` к методу PHP-класса.

При необходимости можно изменить порядок вызова методов настройки класса [через атрибут `Kaspi\DiContainer\Attributes\SetupPriority`](setup-priority.md).

<!--@include: ../_include/term_notes.md-->
