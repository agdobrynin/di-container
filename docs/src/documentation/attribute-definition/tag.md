# Tag

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Tag` устанавливает тег и мета-данные для PHP класса.

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
- `$options` – мета-данные тега.
- `$priority` – приоритет для сортировки в коллекции тегов.
- `$priorityMethod` – метод-приоритета в PHP классе для сортировки в коллекции тегов, если явно не указано значение параметра `$priority`.

> [!IMPORTANT]
> Метод указанный в `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod` должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
>
> В качестве параметров метод-приоритета получает два параметра:
>  - `string $tag` – имя тега.
>  - `array $options` – метаданные тега.

> [!TIP]
> Более подробное описание в разделе [«Работа с тегами в контейнере»](../05-tags.md).

Указание несколько атрибутов для PHP класса:

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
