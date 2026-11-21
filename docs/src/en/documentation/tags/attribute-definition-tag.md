---
outline: [2,4]
title: Declaring a tag via an attribute
---
### Declaring a tag via an attribute { #title }

The [`\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) attribute declares a tag and metadata for a PHP class.

The [`\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) attribute injects a collection of elements from the container tagged with the same name.

<!--@include: ../_include/need_import_php_class.md-->

### Example of configuration via an attribute { #example-inject-by-attribute-tagged-as }

PHP classes:

::: code-group

```php [RuleCollection.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/RuleCollection.php // [!code highlight]

namespace App\Services;

final class RuleCollection
{
    public function __construct(
        public readonly iterable $rules
    ) {}
}
```

<<< @/_code/src/Rules/RuleA.php

<<< @/_code/src/Rules/RuleB.php

<<< @/_code/src/Rules/RuleC.php
:::

Configure the injection of a collection of container elements—marked with the tag `'tags.lite-rules'` into the `$rules` parameter of the `\App\Services\RuleCollection` class constructor, using the `\Kaspi\DiContainer\Attributes\TaggedAs` attribute.

Assign the tag `'tags.lite-rules'` to each class using the `\Kaspi\DiContainer\Attributes\Tag` attribute.

::: code-group

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

<<< @/_code/src/Rules/RuleA_Attr.php [RuleA.php]

<<< @/_code/src/Rules/RuleB_Attr.php [RuleB.php]

<<< @/_code/src/Rules/RuleC_Attr.php [RuleC.php]

:::

::: code-group
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

### Dependency injection into a parameter of type array { #inject-by-attribute-tagged-as-array-param }

When injecting a collection of elements into a parameter of type `array`, you must set the `$isLazy` parameter in the `\Kaspi\DiContainer\Attributes\TaggedAs` attribute to `false`.

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
        public readonly array $rules
    ) {}
}
```

<<< @/_code/src/Rules/RuleA.php

<<< @/_code/src/Rules/RuleB.php

<<< @/_code/src/Rules/RuleC.php

:::

The `$rules` parameter of the `\App\Services\Rules` class constructor is of type `array`. It is necessary to inject a collection of container elements marked with the tag `'tags.lite-rules'` using the `\Kaspi\DiContainer\Attributes\TaggedAs` attribute.

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

<<< @/_code/src/Rules/RuleA_Attr.php [RuleA.php]

<<< @/_code/src/Rules/RuleB_Attr.php [RuleB.php]

<<< @/_code/src/Rules/RuleC_Attr.php [RuleC.php]

:::

::: code-group
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

foreach ($rules->rules as $rule) {
    var_dump($rule::class);
}
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
bool(true)
string (15) "App\Rules\RuleA"
string (15) "App\Rules\RuleC"
```

The `\App\Rules\RuleB` class is not included in the collection because it is marked with the tag `'tags.other-rulers'`.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

### Implementing a collection for variable-length parameters { #inject-by-attribute-tagged-as-variadic }

A variable-length parameter also supports the inclusion of collections of elements from a container marked with a tag.

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
