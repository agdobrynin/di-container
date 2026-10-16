---
aside: false
---
# Tag

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Tag` устанавливает тег и метаданные для PHP класса.

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
- `$priority` – [приоритет тега](../tags/collection-priority/priority-by-tag.md) в коллекции.
- `$priorityMethod` – «[метод приоритета](../tags/collection-priority/priority-by-method.md)» в PHP классе для сортировки в коллекции тегов, если явно не указано значение параметра `$priority`.

> [!TIP]
> Более подробное описание в разделе [«Работа с тегами в контейнере»](../tags/index.md).

Можно указывать несколько атрибутов `Tag` для PHP класса:

```php
use Kaspi\DiContainer\Attributes\Tag; 
namespace App\Services;

#[Tag(name: 'tags.group-one', priorityMethod: 'getPriority')]
#[Tag(name: 'tags.group-two', priority: 1000)]
final class Foo
{
    // …
    
    public static function getPriority(): string
    {
        return 'group-one:1000';
    }
}
```

<!--@include: ../_include/term_notes.md-->
