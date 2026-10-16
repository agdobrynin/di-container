# diTaggedAs

## Обзор { #overview }

Хелпер функция `diTaggedAs()` внедряет коллекцию элементов из контейнера отмеченных тегом.

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diTaggedAs(
    string $tag,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
): DiDefinitionNoArgumentsInterface
```

Параметры:
- `$tag` – имя тега.
- `$isLazy` – «[ленивая коллекция](../tags/index.md#collection-is-lazy)».
- `$priorityDefaultMethod` – «[метод-приоритета](../tags/collection-priority/priority-by-method-default.md)» по умолчанию.
- `$useKeys` – использовать [идентификаторы элементов коллекции](../tags/using-collecton-key/index.md).
- `$key` – [имя метаданных](../tags/tag-metadata.md) для получения идентификатора элемента коллекции.
- `$keyDefaultMethod` – «[метод идентификатора](../tags/using-collecton-key/key-from-method-default.md)» элемента коллекции по умолчанию.
- `$containerIdExclude` – [исключить из коллекции](../tags/exclide-from-collection.md#exclude-by-id) элементы по идентификатору контейнера.
- `$selfExclude` – [исключить из коллекции вызывающий PHP класс](../tags/exclide-from-collection.md#exclide-self).

У хелпер функции нет дополнительных методов.

<!--@include: ../_include/need_import_php_class.md-->

> [!NOTE]
> Подробное описание работы с тегами в разделе «[Работа с тегами в контейнере](../tags/index.md)».

## Пример использования хелпер функции { #example-inject-by-helper-fn-di-tagged }

<!--@include: _include/di-tagged-as-example.md-->

<!--@include: ../_include/term_notes.md-->
