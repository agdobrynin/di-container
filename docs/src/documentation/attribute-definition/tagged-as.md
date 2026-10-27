# TaggedAs

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs` внедряет коллекцию элементов из контейнера отмеченных тегом с одинаковым имененм.

Конфигурация атрибута:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\TaggedAs::__construct(
    string $name,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
)
```

Параметры:
- `$name` – имя тега или FQNC [^FQCN] интерфейса.
- `$isLazy` – «ленивая коллекция».
- `$priorityDefaultMethod` – «метод-приоритета» по умолчанию.
- `$useKeys` – строковые идентификаторы элементов коллекции.
- `$key` – имя метаданных в теге коллекции.
- `$keyDefaultMethod` – «метод идентификатора» элемента коллекции по умолчанию.
- `$containerIdExclude` – исключить из коллекции элементы по идентификатору контейнера.
- `$selfExclude` – исключить из коллекции вызывающий PHP класс.

<!--@include: ../_include/need_import_php_class.md-->

> [!TIP]
> Изучите разделы документации
> - «[Настройка тегов через php атрибут](../tags/attribute-definition-tag.md)».
> - «[Работа с тегами в контейнере](../tags/index.md)».

<!--@include: ../_include/term_notes.md-->
