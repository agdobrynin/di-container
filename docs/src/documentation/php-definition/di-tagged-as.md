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
- `$priorityDefaultMethod` – если получаемый элемент коллекции является PHP классом и у него не определен параметр `$priority` или `$priorityMethod`, то будет выполнена попытка получить значение приоритета через вызов указанного метода.
- `$useKeys` – использовать именованные строковые ключи в коллекции. По умолчанию в качестве ключа элемента в коллекции используется идентификатор контейнера [^ContainerId] элемента коллекции.
- `$key` – использовать ключ в коллекции для элемента из [метаданных тега](../tags/tag-metadata.md).
- `$keyDefaultMethod` – если получаемый элемент коллекции является PHP классом и у него не определен `$key`, то будет выполнена попытка получить значение ключа тега через вызов указанного метода.
- `$containerIdExclude` – исключить из коллекции элементы по идентификаторам контейнера [^ContainerId].
- `$selfExclude` – исключить PHP класс из коллекции если имя тега совпадает с именем тега коллекции.

У хелпер функции нет дополнительных методов.

Внедрение зависимости применяется для параметров с типом:
- `iterable`
    - `\Traversable`
        - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` (_требуется передать аргумент `false` для параметра `$isLazy`_)
- `\ArrayAccess & \Iterator & \Psr\Container\ContainerInterface`

> [!NOTE]
> Подробное описание работы с тегами в разделе «[Работа с тегами в контейнере](../tags/index.md)».

## Пример использования хелпер функции { #example }

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
            rules: diTaggedAs('tags.lite-rules')
        );

    yield diAutowire(RuleA::class)
        ->bindTag('tags.lite-rules');

    // 🚩 класс не отмеченный тегом
    yield diAutowire(RuleB::class);

    yield diAutowire(RuleC::class)
        ->bindTag('tags.lite-rules', priority: 100);
};
```

Классы для конфигурирования:

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

Свойство `\App\Services\RuleCollection::$rules` содержит коллекцию классов отсортированную по `'priority'`.
Класс `\App\Rules\RuleB` не попадает в коллекцию так как не отмечен тегом `'tags.lite-rules'`.

<!--@include: ../_include/term_notes.md-->
