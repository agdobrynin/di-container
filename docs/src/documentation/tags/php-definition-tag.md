# Объявление тега в файлах конфигураций { #title }

## Обзор { #overview }

Метод `bindTag()`[^BindTag], описанный интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`, объявляет тег и метаданные.

[Хелпер функция `diTaggedAs()`](../php-definition/di-tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом с одинаковым именем.

<!--@include: ../_include/need_import_php_class.md-->

## Пример использования хелпер функции { #example-inject-by-helper-fn-di-tagged }

Объявить тег `'tags.lite-rules'` для каждого класса через метод `bindTag()`[^BindTag] в [файле конфигураций](../container-builder/configuration_files.md) `rule_services.php`.

Объявить внедрение коллекции из элементов контейнера отмеченных тегом `'tags.lite-rules'` в параметр `$rules` конструктора класса `\App\Services\RuleCollection` через [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md) в файле конфигураций `services.php`.

::: code-group

<<< @/_code/config/rule_services.php

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\RuleCollection;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(RuleCollection::class)
        ->bindArguments(
            rules: diTaggedAs('tags.lite-rules')// [!code ++]
        );
};
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\RuleCollection;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/rule_services.php')// [!code highlight]
    ->build();

$ruleCollection = $container->get(RuleCollection::class);

foreach ($ruleCollection->rules as $rule) {
    var_dump($rule::class);
}

// console output:
// string (15) "App\Rules\RuleA"
// string (15) "App\Rules\RuleC"
```

```php [RuleCollection.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/RuleCollection.php // [!code highlight]

namespace App\Services;

final class RuleCollection
{
    public function __construct(public readonly iterable $rules) {}
}
```
<<< @/_code/src/Rules/RuleA.php

<<< @/_code/src/Rules/RuleB.php

<<< @/_code/src/Rules/RuleC.php

:::

Класс `\App\Rules\RuleB` не попадает в коллекцию так как отмечен тегом `'tags.other-rulers'`.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

## Внедрение зависимости в параметр с типом array { #inject-by-di-tagged-array-param }

При внедрении коллекции элементов из контейнера отмеченных тегом в параметр с типом `array` обязательно установить значение `false` для параметра `$isLazy` в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md).

Параметр `$rules` конструктора класса `\App\Services\Rules` представлен типом `array`. Необходимо внедрить коллекцию из элементов контейнера отмеченных тегом `'tags.lite-rules'` через [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md) в файле конфигураций `services.php`.

::: code-group

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php //[!code highlight]

use App\Services\Rules;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Rules::class)
        ->bindArguments(
            rules: diTaggedAs(// [!code highlight]
                'tags.lite-rules',// [!code highlight]
                isLazy: false,// [!code ++]
            )// [!code highlight]
        );
};
```

<<< @/_code/config/rule_services.php

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Rules;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/rule_services.php')// [!code highlight]
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

```php [Rules.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Rules.php // [!code highlight]

namespace App\Services;

final class Rules
{
    public function __construct(
        public readonly array $rules// [!code highlight]
    ) {}
}
```
<<< @/_code/src/Rules/RuleA.php

<<< @/_code/src/Rules/RuleB.php

<<< @/_code/src/Rules/RuleC.php

:::

Класс `\App\Rules\RuleB` не попадает в коллекцию так как отмечен тегом `'tags.other-rulers'`.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

## Внедрение коллекции для параметров переменной длины { #inject-by-di-tagged-as-variadic }

Параметр переменной длины так же поддерживает внедрение коллекций элементов из контейнера отмеченных тегом.

::: code-group

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php // [!code highlight]

use App\Services\Bar;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindArguments(
            srvGroup_1: diTaggedAs('tags.group_1'),//[!code ++]
            srvGroup_2: diTaggedAs('tags.group_2'),//[!code ++]
        );
};
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php  // [!code highlight]

namespace App\Services;

class Bar
{
    public function __construct(iterable ...$srvGroup) {}

}
```

:::

> [!TIP]
> <!--@include: ../_include/variadic_params.md-->

<!--@include: ../_include/term_notes.md-->
<!--@include: ../php-definition/_include/bind_notes.md-->
