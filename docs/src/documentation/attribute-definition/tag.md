---
aside: false
---
# Tag

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Tag` объявляет тег и метаданные для PHP класса.

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Tag::__construct(
    string $name,
    array $options = [],
    int|null|string $priority = null,
    ?string $priorityMethod = null
)
```

Параметры:
- `$name` – имя тега.
- `$options` – [метаданные тега](../tags/tag-metadata.md).
- `$priority` – [приоритет тега](../tags/collection-priority/index.md) в коллекции.
- `$priorityMethod` – «[метод приоритета](../tags/collection-priority/index.md)».

> [!TIP]
> Более подробное описание в разделе [«Работа с тегами в контейнере»](../tags/index.md).

<!--@include: ../_include/term_notes.md-->
