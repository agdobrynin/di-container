## Пример конфигурирования через атрибут { #example-inject-by-attribute-tagged-as }

Необходимо внедрить в параметр `\App\Services\RuleCollection::$rules` «ленивую коллекцию» из PHP классов отмеченных тегом `'tags.lite-rules'`.

::: code-group
```php [RuleCollection.php]
// file: /app/src/Services/RuleCollection.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class RuleCollection
{
    public function __construct(
        #[TaggedAs('tags.lite-rules')]
        public readonly iterable $rules
    ) {}
}
```

```php [RuleA.php]
// file: /app/src/Rules/RuleA.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.lite-rules')]
final class RuleA
{
    // …
}
```

```php [RuleB.php]
// file: /app/src/Rules/RuleB.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.other-rulers')]// [!code warning]
final class RuleB
{
    // …
}
```

```php [RuleC.php]
// file: /app/src/Rules/RuleC.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.lite-rules', priority: 100)]
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
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$ruleCollection = $container->get(RuleCollection::class);

foreach ($ruleCollection->rules as $rule) {
    var_dump($rule::class);
}

// вывод консоли:
// string (15) "App\Rules\RuleC"
// string (15) "App\Rules\RuleA"
```

Свойство `\App\Services\RuleCollection::$rules` содержит коллекцию классов отсортированную по значению в параметре `\Kaspi\DiContainer\Attributes\Tag::$priority`.

Класс `\App\Rules\RuleB` не попадает в коллекцию, так как не отмечен тегом `'tags.lite-rules'`.

## Внедрение зависимости в параметр с типом `array` { #inject-by-attribute-tagged-as-array-param }

При внедрении коллекции элементов из контейнера отмеченных тегом в параметр с типом `array` обязательно передать значение `false` для параметра `$isLazy` в [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

class Qux
{
    public function __construct(
        #[TaggedAs(
            'tags.lite-rules',
            isLazy: false,// [!code ++]
        )]
        public readonly array $collection// [!code highlight]
    ) {}

}
```
