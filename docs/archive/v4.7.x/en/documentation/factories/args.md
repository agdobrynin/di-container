---
outline: [2,4]
title: Arguments for a factory method
---
### Arguments for a factory method { #title }

When declaring dependency injection or defining a PHP class via a factory class[^FactoryPattern], the parameters of the factory class's constructor and methods can be injected automatically based on the container configuration.

For specific scenarios, you can define how dependencies should be injected into particular parameters of a factory class method.

When declaring injection within [configuration files](../container-builder/configuration_files.md), arguments are passed to the `bindArguments()` method of the [`diFactory()`](../php-definition/di-factory.md#bindarguments) helper function.

When declaring injection via the [`\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md#args) attribute, arguments are specified using the `$arguments` parameter.

### Example of passing arguments to a PHP class factory method { #example }

PHP classes:

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(private readonly string $value) {}
}

```
```php [Bar.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Bar.php// [!code highlight]

namespace App\Services;

final class Bar
{
    public function __construct(private readonly Baz $baz) {}
    
    public function createValue(string $prefixValue): string
    {
        return $prefixValue.' '.$this->baz->doSomething();
    }
}

```

```php [Baz.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

final class Baz
{
    // …

    public function doSomething(): string
    {
        return 'dolor sit amet';
    }
}
```
:::

It is necessary to declare the injection into the constructor parameter `$value` of the PHP class `\App\Services\Foo` of the value that is calculated by the `\App\Services\Bar::createValue()` method.

In this example, `\App\Services\Bar::createValue()` is a “factory” that is delegated the process of creating the required value.

In the constructor parameter `$baz` of the PHP class `\App\Services\Bar` the container will inject the dependency on its own, based on the configuration.

For the `$prefixValue` parameter of the `\App\Services\Bar::createValue()` factory method, declare a scalar type injection via a [container parameter](../container-parameters/index.md) named `'params.prefix_value'`.

#### Declaration in configuration files { #example-php-definition }

In the configuration file `services.php` we declare the PHP class `\App\Services\Foo` and inject the dependency into the constructor parameter `$value` through the helper function [`diFactory()`](../php-definition/di-factory.md).

For the `$prefixValue` parameter of the `\App\Services\Bar::createValue()` factory method, we will declare the implementation of a scalar value through the [`diParameter()`](../php-definition/di-parameter.md) helper function.

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code ++]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->value);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php// [!code highlight]

use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diFactory, diParameter};

return static function(): Generator {
    yield diAutowire(Foo::class)// [!code highlight]
        ->bindArguments(// [!code highlight]
            value: diFactory(['Bar::class', 'createValue'])// [!code ++]
                ->bindArguments(// [!code ++]
                    prefixValue: diParameter('params.prefix_value')// [!code ++]
                )// [!code ++]
        );// [!code highlight]
};
```

:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
string(26) "Lorem ipsum dolor sit amet"
```

#### Declaration via an attribute { #example-attribute }

We will declare dependency injection for the constructor parameter `$value` using the [`DiFactory`](../attribute-definition/di-factory.md) attribute within the PHP class `\App\Services\Foo`.

For the `$prefixValue` parameter of the factory method `\App\Services\Bar::createValue()`, we will declare the injection of a scalar value using the PHP class `\Kaspi\DiContainer\DiDefinition\DiDefinitionParameter`.

<!--@include: ../attribute-definition/_include/to_arguments.md-->

PHP classes:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;//[!code ++]

final class Foo
{
    public function __construct(
        #[DiFactory(//[!code ++]
            ['Bar::class', 'createValue'],//[!code ++]
            arguments: [//[!code ++]
                'prefixValue' => new DiParameter('params.prefix_value')//[!code ++]
            ],//[!code ++]
        )]//[!code ++]
        private readonly string $value
    ) {}
}

```
:::

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code ++]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->value);
```
:::

Run the following command in the terminal:

```shell
php /app/src/container.php
```

A message like the following will appear:

```console
string(26) "Lorem ipsum dolor sit amet"
```

<!--@include: ../_include/term_notes.md-->
