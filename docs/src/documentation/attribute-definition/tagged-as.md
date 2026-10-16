# TaggedAs

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs` внедряет коллекцию элементов из контейнера отмеченных тегом.

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
- `$name` – имя тега или FQNC [^FQCN] PHP интерфейса по которому нужно собрать коллекцию из контейнера.
- `$isLazy` – «[ленивая коллекция](../tags/index.md#collection-is-lazy)».
- `$priorityDefaultMethod` – «[метод-приоритета](../tags/collection-priority/priority-by-method-default.md)» по умолчанию.
- `$useKeys` – использовать [идентификаторы элементов коллекции](../tags/using-collecton-key/index.md).
- `$key` – [имя метаданных](../tags/tag-metadata.md) для получения идентификатора элемента коллекции.
- `$keyDefaultMethod` – «[метод идентификатора](../tags/using-collecton-key/key-from-method-default.md)» элемента коллекции по умолчанию.
- `$containerIdExclude` – [исключить из коллекции](../tags/exclide-from-collection.md#exclude-by-id) элементы по идентификатору контейнера.
- `$selfExclude` – [исключить из коллекции вызывающий PHP класс](../tags/exclide-from-collection.md#exclide-self).

<!--@include: ../_include/need_import_php_class.md-->

> [!NOTE]
> Раздел документации «[Настройка тегов через php атрибут](../tags/attribute-definition-tag.md)».
> 
> Для подробного ознакомления с механизмом формирования **приоритетов элементов коллекции, идентификаторов элементов коллекции** изучите раздел документации «[Работа с тегами в контейнере](../tags/index.md)».

<!--@include: ../_include/term_notes.md-->
