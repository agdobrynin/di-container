---
outline: [2, 4]
title: PHP interface as a tag name
---
### PHP interface as a tag name { #title }

The FQCN[^FQCN] of an interface implemented by the classes can be used as the tag name.

A collection injection is declared using the [`diTaggedAs()`](../php-definition/di-tagged-as.md) helper function or the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) attribute.

<!--@include: ../_include/need_import_php_class.md-->

As an example, let’s look at the `\App\Services\FooValidator` class:

::: code-group

<<< @/_code/src/Services/FooValidator.php

<<< @/_code/src/Interfaces/RuleInterface.php

:::

It is necessary to declare the injection of a collection of PHP classes all of which implement the `\App\Interfaces\RuleInterface` interface into the `$rules` constructor parameter of the class.

Below are the PHP classes that perform a specific data validation:

::: code-group

<<< @/_code/src/ValidateRules/RuleA.php

<<< @/_code/src/ValidateRules/RuleB.php

<<< @/_code/src/ValidateRules/RuleC.php

:::

An important note: the PHP class `\App\ValidateRules\RuleB` does not implement the `\App\Interfaces\RuleInterface` interface via the `implements` keyword.

#### Declaration in configuration files  { #as-php-definition }

Declare the injection of a collection of PHP classes that implement the `\App\Interfaces\RuleInterface` interface into the `$rules` constructor parameter of the `\App\Services\FooValidator` PHP class.


In the `services.php` [configuration file](../container-builder/configuration_files.md), you must specify the FQCN of the interface `\App\Interfaces\RuleInterface` within the `$tag` parameter of the `diTaggedAs()` helper function.

To register other PHP classes in the container, we use the [`DiContainerBuilder::import()` method](../container-builder/methods/import.md).

::: code-group


```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\FooValidator;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$validator = $container->get(FooValidator::class);

foreach ($validator->rules as $rule) {
    var_dump($rule::class);
}
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Interfaces\RuleInterface;
use App\Services\FooValidator;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(FooValidator::class)
        ->bindArguments(
            rules: diTaggedAs(RuleInterface::class)//[!code ++]
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
string (22) "App\ValidateRules\RuleA"
string (22) "App\ValidateRules\RuleC"
```

The class `\App\ValidateRules\RuleB` was not included in the collection because it does not implement the `App\Interfaces\RuleInterface` interface.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

#### Declaration via attributes { #as-php-attribute }

Configure the injection of a collection of PHP classes that implement the `\App\Interfaces\RuleInterface` interface into the `$rules` constructor parameter of the `\App\Services\FooValidator` PHP class.

In the `\Kaspi\DiContainer\Attributes\TaggedAs` attribute, the `$name` parameter must be set to the FQCN of the interface: `\App\Interfaces\RuleInterface`.

::: code-group

<<< @/_code/src/Services/FooValidatorAttr.php [FooValidator.php]

::: 

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\FooValidator;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$validator = $container->get(FooValidator::class);

foreach ($validator->rules as $rule) {
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
string (22) "App\ValidateRules\RuleA"
string (22) "App\ValidateRules\RuleC"
```

The class `\App\ValidateRules\RuleB` was not included in the collection because it does not implement the `App\Interfaces\RuleInterface` interface.

> [!NOTE]
> <!--@include: _include/random_order_tag.md-->

### Ordering of collection elements { #priority-by-method-default }

When using an interface's FQCN as a tag name, the order of elements in the injected collection is undefined.

To control the order of elements in such a collection, you can use the `$priorityDefaultMethod` parameter available in the [`diTaggedAs()`](../php-definition/di-tagged-as.md) helper function or the [`\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) attribute to specify a default priority method.

You must add a static method to the relevant PHP class, this method should return the priority value for the collection:

```php
public static function getPriority(): int // [!code ++]
{// [!code ++]
    // Each class has its own priority// [!code ++]
    return 100;// [!code ++]
}// [!code ++]
```

If a priority method is not defined by default in a PHP class that serves as an element of a collection, the priority value will be `null`.

For the `diTaggedAs()` helper function, specify the priority method `getPriority`:

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php

use App\Interfaces\RuleInterface;
use App\Services\FooValidator;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(FooValidator::class)
        ->bindArguments(
            rules: diTaggedAs(
                RuleInterface::class,
                priorityDefaultMethod: 'getPriority',//[!code ++]
            )
        );
};
```

For the `\Kaspi\DiContainer\Attributes\TaggedAs` attribute, specify the priority method `getPriority`:

```php
<?php
declare(strict_types=1);

// file: /app/src/Services/FooValidator.php //[!code highlight]

namespace App\Services;

use App\Interfaces\RuleInterface;
use Kaspi\DiContainer\Attributes\TaggedAs;

final class FooValidator
{
    /**
     * @param iterable<RuleInterface> $rules
     */
    public function __construct(
        #[TaggedAs(
            RuleInterface::class,
            priorityDefaultMethod: 'getPriority',//[!code ++]
        )]
        public readonly iterable $rules
    ) {}

    public function validate(mixed $input): mixed
    {
        foreach ($this->rules as $rule) {
            $input = $rule->validate($input);
        }

        return $input;
    }
}
```

⬆️ Review the information in the “[Collection element priority](collection-priority/index.md)” section of the documentation.

<!--@include: ../_include/term_notes.md-->