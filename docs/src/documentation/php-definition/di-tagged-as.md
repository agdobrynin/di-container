---
aside: false
title: Хелпер функция diTaggedAs()
---
### Хелпер функция diTaggedAs()

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
- `$name` – имя тега или FQNC [^FQCN] PHP интерфейса.
- `$isLazy` – «ленивая коллекция».
- `$priorityDefaultMethod` – извлечение приоритета из метода PHP-класса по умолчанию.
- `$useKeys` – использовать строковые идентификаторы элементов коллекции.
- `$key` – извлечение идентификатора элемента коллекции по имени метаданных из тега.
- `$keyDefaultMethod` – извлечение идентификатора элемента коллекции из метода PHP-класса по умолчанию.
- `$containerIdExclude` – исключить из коллекции элементы по идентификатору контейнера.
- `$selfExclude` – исключить из коллекции вызывающий PHP-класс.

У хелпер функции нет дополнительных методов.

<!--@include: ../_include/need_import_php_class.md-->

> [!TIP]
> Изучите раздел документации «[Объявление тега в файлах конфигураций](../tags/php-definition-tag.md)».
> 
> Для подробного ознакомления с механизмом формирования **приоритетов элементов коллекции, идентификаторов элементов коллекции** изучите раздел документации «[Работа с тегами в контейнере](../tags/index.md)».


<!--@include: ../_include/term_notes.md-->
