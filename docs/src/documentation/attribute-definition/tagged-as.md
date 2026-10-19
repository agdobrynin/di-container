# TaggedAs

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs` внедряет коллекцию элементов из контейнера отмеченных тегом.

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
- `$name` – имя тега или FQNC [^FQCN] PHP интерфейса.
- `$isLazy` – «[ленивая коллекция](../tags/index.md#collection-is-lazy)».
- `$priorityDefaultMethod` – «[метод-приоритета](../tags/collection-priority/priority-by-method-default.md)» по умолчанию.
- `$useKeys` – использовать [идентификаторы элементов коллекции](../tags/using-collecton-key/index.md).
- `$key` – [имя метаданных](../tags/using-collecton-key/key-in-metadata.md) получаемое из тега.
- `$keyDefaultMethod` – «[метод идентификатора](../tags/using-collecton-key/key-from-method-default.md)» элемента коллекции по умолчанию.
- `$containerIdExclude` – [исключить из коллекции](../tags/exclide-from-collection.md#exclude-by-id) элементы по идентификатору контейнера.
- `$selfExclude` – [исключить из коллекции вызывающий PHP класс](../tags/exclide-from-collection.md#exclide-self).

<!--@include: ../_include/need_import_php_class.md-->

> [!NOTE]
> Изучите раздел документации «[Настройка тегов через php атрибут](../tags/attribute-definition-tag.md)».
> 
> Для подробного ознакомления с механизмом формирования **приоритетов элементов коллекции, идентификаторов элементов коллекции** изучите раздел документации «[Работа с тегами в контейнере](../tags/index.md)».

<!--@include: ../_include/term_notes.md-->
