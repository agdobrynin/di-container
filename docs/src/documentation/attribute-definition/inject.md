# Inject

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Inject` внедряет зависимость через обращение по идентификатору контейнера [^ContainerId], это эквивалентно вызову метода `get()` контейнера. 

> [!TIP]
> Контейнер может на основании Type Hints [^TypeHints] внедрять зависимости. Если тип параметра совпадает с требуемым внедрением, то указывать атрибут `\Kaspi\DiContainer\Attributes\Inject` нет необходимости, контейнер сделает это автоматически на основании конфигурации.

Конфигурация атрибута:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Inject::__construct(
    string $id = ''
)
```

Параметры:
- `$id` - идентификатор контейнера [^ContainerId].

Пустая строка в параметре `$id` интерпретируется контейнером как внедрение на основании типа параметра[^TypeHints].

> [!WARNING]
> При внедрении зависимости для составного типа (_union, intersection types_) может быть выброшено исключение, [для исправления этой ошибки необходима конкретизация типа](../../cookbook/inject-union-type-params.md#php-attributes).

В разделе «[Рецепты](../../cookbook/inject-interface.md#attribute-inject)» представлен пример использования атрибута для параметра с типом PHP интерфейс. 

<!--@include: ../_include/term_notes.md-->
