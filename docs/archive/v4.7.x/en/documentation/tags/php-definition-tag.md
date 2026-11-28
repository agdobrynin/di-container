---
outline: [2,4]
title: Declaring a tag in configuration files
---
### Declaring a tag in configuration files { #title }

The `bindTag()`[^BindTag] method, defined by the `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface` interface, declares a tag and its metadata.

[The `diTaggedAs()` helper function](../php-definition/di-tagged-as.md) injects a collection of items from the container that are marked with a tag of the same name.

<!--@include: ../_include/need_import_php_class.md-->

### Example of use { #example-inject-by-helper-fn-di-tagged }

PHP classes:

::: code-group

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

Declare the `'tags.lite-rules'` tag for each class using the `bindTag()`[^BindTag] method in the `rule_services.php` [configuration file](../container-builder/configuration_files.md).

Register the injection of a collection of container elements marked with the `'tags.lite-rules'` tag into the `$rules` parameter of the `\App\Services\RuleCollection` class constructor, using the [`diTaggedAs()` helper function](../php-definition/di-tagged-as.md) within the `services.php` configuration file.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\RuleCollection;

$container = (new DiContainerBuilder())
    ->load('/app/config/rule_services.php')// [!code highlight]
    ->load('/app/config/services.php')// [!code highlight]
    ->build();

$ruleCollection = $container->get(RuleCollection::class);

foreach ($ruleCollection->rules as $rule) {
    var_dump($rule::class);
}
```

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

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string (15) "App\Rules\RuleA"
string (15) "App\Rules\RuleC"
```

The `\App\Rules\RuleB` class is not included in the collection because it is marked with the tag `'tags.other-rulers'`.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

### Dependency injection into a parameter of type array { #inject-by-di-tagged-array-param }

When injecting a collection of elements from a container—identified by a specific tag — into a parameter of type `array`, you must set the `$isLazy` argument to `false` within the [`diTaggedAs()` helper function](../php-definition/di-tagged-as.md).

PHP classes:

::: code-group

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

The `$rules` parameter of the `\App\Services\Rules` class constructor is of type `array`. You need to inject a collection of container elements marked with the `'tags.lite-rules'` tag using the [`diTaggedAs()` helper function](../php-definition/di-tagged-as.md) within the `services.php` configuration file.

::: code-group


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
```

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

:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
string (15) "App\Rules\RuleA"
string (15) "App\Rules\RuleC"
```

The `\App\Rules\RuleB` class is not included in the collection because it is marked with the tag `'tags.other-rulers'`.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

### Implementing a collection for variable-length parameters{ #inject-by-di-tagged-as-variadic }

A variable-length parameter also supports the inclusion of collections of elements from a container marked with a tag.

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
            rules_liteRules: diTaggedAs('tags.lite-rules'),//[!code ++]
            rules_otherRules: diTaggedAs('tags.other-rulers'),//[!code ++]
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
    public function __construct(iterable ...$rules) {}

}
```

:::

> [!TIP]
> <!--@include: ../_include/variadic_params.md-->

<!--@include: ../_include/term_notes.md-->
<!--@include: ../php-definition/_include/bind_notes.md-->
