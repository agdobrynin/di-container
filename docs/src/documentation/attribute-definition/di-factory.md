---
outline: [2, 4]
---
# DiFactory

## Обзор { #overview }

Объявление через атрибут `\Kaspi\DiContainer\Attributes\DiFactory` реализуется через паттерн «фабрика» [^FactoryPattern]. Атрибут объявляет PHP-класс в контейнере, или [внедряет зависимость](#di-factory-param).

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\DiFactory::__construct(
    string|array $definition,
    ?bool $isSingleton = null,
    array $arguments = []
)
```

Параметры:
- `$definition` – FQCN[^FQCN] и имя метода.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments` – аргументы метода фабрики.

## Аргументы метода фабрики { #args }

Параметр `$arguments` атрибута `\Kaspi\DiContainer\Attributes\DiFactory` передает аргументы методу фабрики.

<!--@include: _include/arguments.md-->

<!--@include: _include/to_arguments.md-->

> [!TIP]
> Изучите раздел документации [«Использование фабричных методов для внедрения зависимостей»](../factories/index.md).


<!--@include: ../_include/term_notes.md-->
