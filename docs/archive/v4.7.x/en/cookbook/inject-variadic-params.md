---
outline: [3, 5]
title: Dependency injection into variable-length parameters
---

### Dependency injection into variable-length parameters { #overview }

When injecting dependencies into a variable length parameter, you can use combinations of helper functions or PHP attributes. The type checking [^TypeHints] of the injected dependency occurs at runtime - at the time of execution, the container does not perform type validation when [building the container](../documentation/container-builder/index.md).

> [!WARNING]
> A variable-length parameter is optional. If no argument is provided, it will be omitted during dependency injection.

Let’s look at an example of declaring the inclusion of a variable-length argument.

### PHP classes { #php-classes }

::: code-group
<<< @/_code/cookbook/inject-variadic-params/src/Services/Rules.php
<<< @/_code/cookbook/inject-variadic-params/src/Interfaces/RuleInterface.php
<<< @/_code/cookbook/inject-variadic-params/src/Rules/RuleA.php
<<< @/_code/cookbook/inject-variadic-params/src/Rules/RuleB.php
<<< @/_code/cookbook/inject-variadic-params/src/Rules/RuleC.php
:::

It is necessary to configure the injection of dependencies into the variadic `$inputRule` constructor parameter of the PHP class `\App\Services\Rules`. The PHP classes should be injected in the following order:
- `\App\Rules\RuleB`
- `\App\Rules\RuleA`
- `\App\Rules\RuleC`

The container provides multiple ways to declare the injection of a PHP class into a parameter; for this example, we will use either the helper function [`diGet()`](../documentation/php-definition/di-get.md) or the attribute [`\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md), depending on the method used to declare the dependency injection.

---

#### Declaration in configuration files { #php-definition }

In the `services.php` configuration file, register the PHP class `\App\Services\Rules` using the `diAutowire()` helper function, and inject dependencies into the `$inputRule` parameter via the [`bindArguments()`](../documentation/php-definition/di-autowire.md#bindarguments) method.

::: code-group
<<< @/_code/cookbook/inject-variadic-params/container_build.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Services\Rules;
use App\Rules\{RuleC, RuleB, RuleA};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield 'services.rule_c' => diAutowire(RuleC::class);//[!code highlight]
    
    // many lines

    yield diAutowire(Rules::class)
        ->bindArguments(//[!code ++]
            diGet(RuleB::class),//[!code ++]
            diGet(RuleA::class),//[!code ++]
            diGet('services.rule_c'), // reference to a container identifier//[!code ++]
        );//[!code ++]
};
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
array(3) {
  [0]=>
  object(App\Rules\RuleB)#26 (0) {
  }
  [1]=>
  object(App\Rules\RuleA)#28 (0) {
  }
  [2]=>
  object(App\Rules\RuleC)#21 (0) {
  }
}
```

The order of objects injected via the constructor into the `\App\Services\Rules::$rules` property is determined by the order in which the injections are declared in the `services.php` configuration file.

##### Именованные аргументы { #named-arguments }

Suppose you need to inject PHP classes into the `\App\Services\Rules::$rules` property, using string values as keys for the array. To achieve this, you can use named arguments when defining the injection for the `$inputRule` constructor parameter within the `services.php` configuration file.

When defining an injection using [named arguments](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments) for [variadic parameters](https://www.php.net/manual/en/functions.arguments.php#functions.variable-arg-list), the rules outlined in the PHP documentation apply.

::: code-group
<<< @/_code/cookbook/inject-variadic-params/container_build.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Services\Rules;
use App\Rules\{RuleB, RuleA};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Rules::class)
        ->bindArguments(//[!code ++]
            inputRule_B: diGet(RuleB::class),//[!code ++]
            inputRule_A: diGet(RuleA::class),//[!code ++]
        );//[!code ++]
};
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console{2,5}
array(2) {
  ["inputRule_B"]=>
  object(App\Rules\RuleB)#26 (0) {
  }
  ["inputRule_A"]=>
  object(App\Rules\RuleA)#28 (0) {
  }
}
```

When using string keys in the `\App\Services\Rules::$rules` property, you can access injected classes by specifying the argument name:

```php
$rules = $container->get(Rules::class);

$ruleA = $rules->rules['inputRule_A'];
$ruleB = $rules->rules['inputRule_B'];
```

> [!TIP]
> For implementing collections, it is preferable to use [tags](../documentation/tags/index.md).

---

#### Declaration via an attribute { #php-attributes }

Let’s declare the injection of a dependency into the `$inputRule` constructor parameter of the PHP class `\App\Services\Rules` using the [`\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) attribute.

::: code-group

```php [Rules.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Rules.php [!code highlight]

namespace App\Services;

use App\Interfaces\RuleInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]
use App\Rules\{RuleB, RuleA, RuleC};//[!code ++]

final class Rules
{
    /** @var RuleInterface[] */
    public readonly array $rules;//[!code highlight]

    public function __construct(
        #[Inject(RuleB::class)]//[!code ++]
        #[Inject(RuleA::class)]//[!code ++]
        #[Inject(RuleC::class)]//[!code ++]
        RuleInterface ...$inputRule
    ) {
        $this->rules = $inputRule;
    }
}

```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Rules;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$rules = $container->get(Rules::class);
var_dump($rules->rules);
```
:::

Run the following command in the terminal:

```shell
php /app/src/container_build.php
```

A message like the following will appear:

```console
array(3) {
  [0]=>
  object(App\Rules\RuleB)#26 (0) {
  }
  [1]=>
  object(App\Rules\RuleA)#28 (0) {
  }
  [2]=>
  object(App\Rules\RuleC)#21 (0) {
  }
}
```

The order of objects embedded via a constructor into the `\App\Services\Rules::$rules` property is determined by the order of declaration of injection via an attribute.

<!--@include: ../documentation/_include/term_notes.md-->