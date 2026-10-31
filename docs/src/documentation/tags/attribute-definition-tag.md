# Объявление тега через атрибут { #title }

## Обзор { #overview }

[Атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) объявляет тег и метаданные для PHP класса.

[Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом с одинаковым именем.

<!--@include: ../_include/need_import_php_class.md-->

## Пример конфигурирования через атрибут { #example-inject-by-attribute-tagged-as }

Объявить тег `'tags.lite-rules'` для каждого класса через атрибут `\Kaspi\DiContainer\Attributes\Tag`.


Объявить внедрение коллекции из элементов контейнера отмеченных тегом `'tags.lite-rules'` в параметр `$rules` конструктора класса `\App\Services\RuleCollection` через атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`.

::: code-group

<<< @/_code/src/Rules/RuleA_Attr.php [RuleA.php]

<<< @/_code/src/Rules/RuleB_Attr.php [RuleB.php]

<<< @/_code/src/Rules/RuleC_Attr.php [RuleC.php]

```php [RuleCollection.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/RuleCollection.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;//[!code ++]

final class RuleCollection
{
    public function __construct(
        #[TaggedAs('tags.lite-rules')]//[!code ++]
        public readonly iterable $rules
    ) {}
}
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\RuleCollection;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$ruleCollection = $container->get(RuleCollection::class);

foreach ($ruleCollection->rules as $rule) {
    var_dump($rule::class);
}

// console output:
// string (15) "App\Rules\RuleA"
// string (15) "App\Rules\RuleC"
```

:::

Класс `\App\Rules\RuleB` не попадает в коллекцию так как отмечен тегом `'tags.other-rulers'`.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

## Внедрение зависимости в параметр с типом array { #inject-by-attribute-tagged-as-array-param }

При внедрении коллекции элементов из контейнера отмеченных тегом в параметр с типом `array` обязательно установить значение `false` для параметра `$isLazy` в [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

Параметр `$rules` конструктора класса `\App\Services\Rules` представлен типом `array`. Необходимо внедрить коллекцию из элементов контейнера отмеченных тегом `'tags.lite-rules'` через атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`.

::: code-group

```php [Rules.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Rules.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code highlight]

final class Rules
{
    public function __construct(
        #[TaggedAs(// [!code highlight]
            'tags.lite-rules',// [!code highlight]
            isLazy: false,//[!code ++]
        )]// [!code highlight]
        public readonly array $rules
    ) {}
}
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Rules;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$rules = $container->get(Rules::class);

var_dump(is_array($rules->rules));
// bool(true)

foreach ($rules->rules as $rule) {
    var_dump($rule::class);
}

// console output:
// string (15) "App\Rules\RuleA"
// string (15) "App\Rules\RuleC"
```

<<< @/_code/src/Rules/RuleA_Attr.php [RuleA.php]

<<< @/_code/src/Rules/RuleB_Attr.php [RuleB.php]

<<< @/_code/src/Rules/RuleC_Attr.php [RuleC.php]

:::

Класс `\App\Rules\RuleB` не попадает в коллекцию так как отмечен тегом `'tags.other-rulers'`.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

## Внедрение коллекции для параметров переменной длины { #inject-by-attribute-tagged-as-variadic }

Параметр переменной длины так же поддерживает внедрение коллекций элементов из контейнера отмеченных тегом.

```php
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;//[!code ++]

final class Qux
{
    public function __construct(
        #[TaggedAs('tags.lite-rules')]//[!code ++]
        #[TaggedAs('tags.other-rulers')]//[!code ++]
        iterable ...$rules
    ) {}

}
```

> [!TIP]
> <!--@include: ../_include/variadic_params.md-->

<!--@include: ../_include/term_notes.md-->
