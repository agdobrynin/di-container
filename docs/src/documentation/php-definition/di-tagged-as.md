# diTaggedAs

## Обзор { #overview }

Функция `diTaggedAs()` позволяет получить коллекцию элементов из контейнера отмеченных тегом.

Результат хелпер функции может быть применен для параметров с типом:
- `iterable`
    - `\Traversable`
        - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` требуется использовать параметр `$isLazy = false`.
- Составной тип (_intersection types_) для ленивых коллекций (`$isLazy = true`)
    - `\ArrayAccess&\Iterator&\Psr\Container\ContainerInterface`.

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
- `$isLazy` – получать элементы коллекции только во время обращения или сразу всё.
- `$priorityDefaultMethod` – если получаемый элемент коллекции является PHP классом и у него не определен `priority` или `priorityMethod`, то будет выполнена попытка получить значение `priority` через вызов указанного метода.
- `$useKeys` – использовать именованные строковые ключи в коллекции. По умолчанию в качестве ключа элемента в коллекции используется идентификатор определения в контейнере (_container identifier_).
- `$key` – использовать ключ в коллекции для элемента из опций тега (_метаданные из `$options` определенные у тега_).
- `$keyDefaultMethod` – если получаемый элемент коллекции является PHP классом и у него не определен `$key`, то будет выполнена попытка получить значение ключа тега через вызов указанного метода.
- `$containerIdExclude` – исключить из коллекции элементы с указанными идентификаторами (_container identifier_).
- `$selfExclude` – исключить PHP класс из коллекции если имя тега совпадает с именем тега коллекции.

У хелпер функции нет дополнительных методов.

> [!NOTE]
> Подробное описание работы с тегами в разделе «[Работа с тегами в контейнере](../05-tags.md)».

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
