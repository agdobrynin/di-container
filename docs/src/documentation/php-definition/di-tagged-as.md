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

## Пример использования хелпер функции { #example }

Необходимо внедрить в параметр `\App\Services\RuleCollection::$rules` «ленивую коллекцию» из PHP классов отмеченных тегом `'tags.lite-rules'`.

PHP классы:

::: code-group

```php [RuleCollection.php]
// file: /app/src/Services/RuleCollection.php
namespace App\Services;

final class RuleCollection
{
    public function __construct(public readonly iterable $rules) {}
}
```

```php [RuleA.php]
// file: /app/src/Rules/RuleA.php
namespace App\Rules;

final class RuleA
{
    // …
}
```

```php [RuleB.php]
// file: /app/src/Rules/RuleB.php
namespace App\Rules;

final class RuleB
{
    // …
}
```

```php [RuleC.php]
// file: /app/src/Rules/RuleC.php
namespace App\Rules;

final class RuleC
{
    // …
}
```

:::

Конфигурирование:

```php
// file: /app/config/services.php
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};
use Generator;
use App\Services\RuleCollection;
use App\Rules\{RuleA, RuleB, RuleC};

return static function (): Generator {

    yield diAutowire(RuleCollection::class)
        ->bindArguments(
            rules: diTaggedAs('tags.lite-rules')// [!code highlight]
        );

    yield diAutowire(RuleA::class)
        ->bindTag('tags.lite-rules');// [!code highlight]

    // 🚩 класс не отмеченный тегом
    yield diAutowire(RuleB::class);

    yield diAutowire(RuleC::class)
        ->bindTag('tags.lite-rules', priority: 100);// [!code highlight]
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\RuleCollection;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build()
;

$ruleCollection = $container->get(RuleCollection::class);

foreach ($ruleCollection->rules as $rule) {
    var_dump($rule::class);
}

// string (15) "App\Rules\RuleC"
// string (15) "App\Rules\RuleA"
```

Свойство `\App\Services\RuleCollection::$rules` содержит коллекцию классов отсортированную по значению в параметре `$priority` метода `bindTag()`.

Класс `\App\Rules\RuleB` не попадает в коллекцию так как не отмечен тегом `'tags.lite-rules'`.

<!--@include: ../_include/term_notes.md-->
