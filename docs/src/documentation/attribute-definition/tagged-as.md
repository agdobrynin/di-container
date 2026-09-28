---
aside: false
title: Атрибут TaggedAs
---
### Атрибут TaggedAs

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
- `$priorityDefaultMethod` – извлечение приоритета из метода PHP-класса по умолчанию.
- `$useKeys` – использовать строковые идентификаторы элементов коллекции.
- `$key` – извлечение идентификатора элемента коллекции по имени метаданных из тега.
- `$keyDefaultMethod` – извлечение идентификатора элемента коллекции из метода PHP-класса по умолчанию.
- `$containerIdExclude` – исключить из коллекции элементы по идентификатору контейнера.
- `$selfExclude` – исключить из коллекции вызывающий PHP-класс.

<!--@include: ../_include/need_import_php_class.md-->

> [!TIP]
> Изучите разделы документации
> - «[Объявление тега через атрибут](../tags/attribute-definition-tag.md)».
> - «[Работа с тегами в контейнере](../tags/index.md)».

<!--@include: ../_include/term_notes.md-->
