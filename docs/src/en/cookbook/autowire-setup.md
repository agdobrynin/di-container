---
outline: [2, 4]
title: Dependency injection via setter methods
---
### Dependency injection via setter methods { #title }

Dependency injection into a PHP class can be performed via **setter methods**.

You can configure dependency injection via setter methods using the [`setup()`](../documentation/php-definition/di-autowire.md#setup) or [`setupImmutable()`](../documentation/php-definition/di-autowire.md#setupimmutable) methods, provided by the `diAutowire()` helper function, or by using the [`\Kaspi\DiContainer\Attributes\Setup`](../documentation/attribute-definition/setup.md) and [`\Kaspi\DiContainer\Attributes\SetupImmutable`](../documentation/attribute-definition/setup-immutable.md) attributes.

### Mutable setter method[^SetterMethodMutable] { #example-setup }

PHP classes:

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo
{
    private QuxInterface $qux;

    public function setQux(QuxInterface $qux): void//[!code warning]
    {//[!code warning]
        $this->qux = $qux;//[!code warning]
    }//[!code warning]

    public function getQux(): ?QuxInterface
    {
        return $this->qux ?? null;
    }
}
```
<<< @/_code/cookbook/autowire-setup/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/autowire-setup/src/Services/Bar.php
:::

You need to configure the injection of the PHP class `\App\Services\Bar` via the mutable setter method `\App\Services\Foo::setQux()`.

The `$qux` parameter of the `\App\Services\Foo::setQux()` method expects an object that implements the `\App\Interfaces\QuxInterface` interface. The container provides [several ways to define](inject-interface/index.md) the injection of a PHP class into a parameter; for this example, we will use either the helper function [`diGet()`](../documentation/php-definition/di-get.md) or the attribute [`\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md), depending on the chosen method for defining the dependency injection.

#### Declaration in configuration files { #example-stup-php-definition }

In the `services.php` configuration file, we will define the PHP class `\App\Services\Foo` and specify injection via the setter method `'setQux'`.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->getQux());
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Service\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->setup(//[!code ++]
            'setQux',//[!code ++]
            [//[!code ++]
                'qux' => diGet(Bar::class),//[!code ++]
            ],//[!code ++]
        );//[!code ++]
};
```
::: 

<!--@include: _include/autowire-setup-result.md-->

#### Declaration via an attribute { #example-stup-attribute }

We will declare the injection of the PHP class `\App\Services\Bar` using the `\Kaspi\DiContainer\Attributes\Setup` attribute, specifying it for the setter method `\App\Services\Foo::setQux()`. To declare the injection into a parameter of the setter method, we use the `\Kaspi\DiContainer\Attributes\Inject` attribute.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]
use Kaspi\DiContainer\Attributes\Setup;//[!code ++]

final class Foo
{
    private QuxInterface $qux;

    public function setQux(QuxInterface $qux): void//[!code --]
    #[Setup]//[!code ++]
    public function setQux(//[!code ++]
        #[Inject(Bar::class)]//[!code ++]
        QuxInterface $qux//[!code ++]
    ): void//[!code ++]
    {
// …
```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->getQux());
```
:::

<!--@include: _include/autowire-setup-result.md-->

### Immutable setter method[^SetterMethodImmutable]

PHP classes:

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Baz
{
    private QuxInterface $qux;

    public function withQux(QuxInterface $qux): self//[!code warning]
    {//[!code warning]
        $new = clone $this;//[!code warning]
        $new->qux = $qux;//[!code warning]
//[!code warning]

        return $new;//[!code warning]
    }//[!code warning]

    public function getQux(): ?QuxInterface
    {
        return $this->qux ?? null;
    }
}
```
<<< @/_code/cookbook/autowire-setup/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/autowire-setup/src/Services/Bar.php
:::

You need to configure the injection of the PHP class `\App\Services\Bar` via the immutable setter method `\App\Services\Baz::withQux()`. Note that this setter method returns a new instance of the `\App\Services\Baz` class.

The `$qux` parameter of the `\App\Services\Baz::withQux()` method expects an object that implements the `\App\Interfaces\QuxInterface` interface. The container offers [various ways to define](inject-interface/index.md) the injection of a PHP class into a parameter; for this example, you can use either the [`diGet()`](../documentation/php-definition/di-get.md) helper function or the [`\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) attribute, depending on the chosen method for defining the dependency injection.

#### Declaration in configuration files { #example-stupimmutable-php-definition}

In the `services.php` configuration file, we will define the PHP class `\App\Services\Baz` and specify dependency injection via the setter method `'withQux'`.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Baz;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$baz = $container->get(Baz::class);
var_dump($baz->getQux());
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Service\{Baz, Bar};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Baz::class)
        ->setupImmutable(//[!code ++]
            'withQux',//[!code ++]
            [//[!code ++]
                'qux' => diGet(Bar::class),//[!code ++]
            ],//[!code ++]
        );//[!code ++]
};
```
:::

<!--@include: _include/autowire-setup-result.md-->

#### Declaration via an attribute { #example-stupimmutable-attribute }

Let's declare the implementation of the PHP class `\App\Services\Bar` through the attribute `\Kaspi\DiContainer\Attributes\SetupImmutable`, specifying it for the setter method `\App\Services\Baz::withQux()`. To declare injection into a setter method parameter, use the `\Kaspi\DiContainer\Attributes\Inject` attribute.

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]
use Kaspi\DiContainer\Attributes\SetupImmutable;//[!code ++]

final class Baz
{
    private QuxInterface $qux;

    public function withQux(QuxInterface $qux): self//[!code --]
    #[SetupImmutable]//[!code ++]
    public function withQux(//[!code ++]
        #[Inject(Bar::class)]//[!code ++]
        QuxInterface $qux//[!code ++]
    ): void//[!code ++]
    {
// …
```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Baz;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$baz = $container->get(Baz::class);
var_dump($baz->getQux());
```
:::

<!--@include: _include/autowire-setup-result.md-->

### Injecting multiple dependencies { #attribute-setup-multiple }

Let’s look at an example involving the injection of multiple dependencies via a single mutable[^SetterMethodMutable] setter method: `\App\Services\Rules::addRule()`.

To declare dependency injection, we use the `\Kaspi\DiContainer\Attributes\Setup` attribute.

The `$inputRule` parameter of the setter method expects an object that implements the `\App\Interfaces\RuleInterface` interface. The container provides [several ways to declare](inject-interface/index.md) the injection of a PHP class into a parameter; for this example, we will use the `\Kaspi\DiContainer\DiDefinition\DiDefinitionGet` PHP class.

<!--@include: ../documentation/attribute-definition/_include/to_arguments.md-->

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Rules;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')//[!code ++]
    ->build();

$rules = $container->get(Rules::class);

foreach($rules->getRules() as $rule) {
    var_dump($rule);
    print PHP_EOL;
}
```

```php [Rules.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Rules.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;//[!code ++]
use App\Rules\{RuleA, RuleB};
use App\Interfaces\RuleInterface;

class Rules
{

    private array $rules = [];
    
    // …
    
    #[Setup(//[!code ++]
        new DiGet(RuleB::class)//[!code ++]
    )]//[!code ++]
    #[Setup(//[!code ++]
        new DiGet(RuleA::class)//[!code ++]
    )]//[!code ++]
    public function addRule(RuleInterface $inputRule): void
    {
        $this->rules[] = $inputRule;
    }
    
    public function getRules(): array
    {
        return $this->rules;
    }
}
```

```php [RuleInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/RuleInterface.php [!code highlight]

namespace App\Interfaces;

interface RuleInterface
{
    // …
}
```

```php [RuleA.php]
<?php
declare(strict_types=1);

// file: /app/src/Rules/RuleA.php [!code highlight]

namespace App\Rules;

use App\Interfaces\RuleInterface;

class RuleA implements RuleInterface
{
    // …
}
```

```php [RuleB.php]
<?php
declare(strict_types=1);

// file: /app/src/Rules/RuleB.php [!code highlight]

namespace App\Rules;

use App\Interfaces\RuleInterface;

class RuleB implements RuleInterface
{
    // …
}
```

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
object(App\Rules\RuleB)#2 (0) {
}

object(App\Rules\RuleA)#2 (0) {
}
```
The order in which objects are injected via a setter method is determined by the sequence in which the `\Kaspi\DiContainer\Attributes\Setup` attribute is applied to the `\App\Services\RuleGenerator::addRule()` method.

<!--@include: ../documentation/_include/term_notes.md-->