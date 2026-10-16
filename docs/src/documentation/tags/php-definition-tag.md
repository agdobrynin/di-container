# 🐘 Настройка тегов в файлах конфигураций { #title }

## Обзор { #overview }

Для указания тегов и метаданных используйте метод `bindTag()` [^BindTag], описанный интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`.

[Хелпер функция `diTaggedAs()`](../php-definition/di-tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом.

<!--@include: ../_include/need_import_php_class.md-->

## Пример использования хелпер функции { #example-inject-by-helper-fn-di-tagged }

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

    yield diAutowire(RuleB::class)
        ->bindTag('tags.other-rulers');// [!code warning]

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

// вывод консоли:
// string (15) "App\Rules\RuleC"
// string (15) "App\Rules\RuleA"
```

Свойство `\App\Services\RuleCollection::$rules` содержит коллекцию классов отсортированную по значению в параметре `$priority` метода `bindTag()`.

Класс `\App\Rules\RuleB` не попадает в коллекцию так как не отмечен тегом `'tags.lite-rules'`.

## Внедрение зависимости в параметр с типом `array` { #inject-by-di-tagged-array-param }

При внедрении коллекции элементов из контейнера отмеченных тегом в параметр с типом `array` обязательно передать значение `false` для параметра `$isLazy` в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md).

:::code-group


```php [tagged_services.php]
// file: /app/config/tagged_services.php
use Generator;
use App\Services\Qux;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Qux::class)
        ->bindArguments(
            rules: diTaggedAs(
                'tags.lite-rules',// [!code highlight]
                isLazy: false,// [!code ++]
            )
        );
};
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

class Qux
{
    public function __construct(
        public readonly array $rules// [!code highlight]
    ) {}

}
```

:::

## Внедрение коллекции для параметров переменной длины { #inject-by-di-tagged-as-variadic }

Параметр переменной длины так же поддерживает внедрение коллекций элементов из контейнера отмеченных тегом.

::: code-group

```php [tagged_services.php]
// file: /app/config/tagged_services.php
use Generator;
use App\Services\Bar;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindArguments(
            srvGroup_1: diTaggedAs('tags.group_1'),
            srvGroup_2: diTaggedAs('tags.group_2'),
        );
};
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

class Bar
{
    public function __construct(iterable ...$srvGroup) {}

}
```

:::

> [!TIP]
> <!--@include: ../_include/variadic_params.md-->

<!--@include: ../php-definition/_include/bind-tag_notes.md-->
