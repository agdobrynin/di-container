# Inject

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Inject` внедряет зависимость через обращение по идентификатору контейнера,
это эквивалентно вызову метода `get()` контейнера зависимостей. 

> [!NOTE]
> Контейнер может на основании Type Hints [^TypeHints] (типа параметра) внедрять зависимости. Если тип параметра совпадает с требуемым внедрением, то указывать атрибут `\Kaspi\DiContainer\Attributes\Inject` нет необходимости, контейнер сделает это автоматически на основании конфигурации.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Inject::__construct(
    string $id = ''
)
```

Параметры:
- `$id` - идентификатор контейнера для внедрения зависимости, так же может быть представлен FQCN [^FQCN].

Если значение в `\Kaspi\DiContainer\Attributes\Inject::$id` пустая строка, то контейнер попытается сформировать значение исходя из типа Type Hints [^TypeHints].

> [!WARNING]
> При разрешении зависимости для составного типа (_union, intersection types_) может быть выброшено исключение, [для исправления этой ошибки необходима конкретизация типа](../../cookbook/inject-union-type-params.md#php-attributes).

В разделе «[Рецепты](../../cookbook/inject-interface.md#attribute-inject)» представлен пример использования атрибута `\Kaspi\DiContainer\Attributes\Inject` для параметра с типом PHP интерфейс. 

!!!!!!!!!!!!!! МОЖЕТ какой-то примерчик простой?

<!--@include: ../_include/term_notes.md-->
