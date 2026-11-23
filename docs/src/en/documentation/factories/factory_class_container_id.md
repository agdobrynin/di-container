---
outline: [2,4]
title: Actual name of the factory class
---
### Actual name of the factory class { #title }

Typically, the PHP class factory[^FactoryPattern] is represented by two string values – the name of the PHP class and the name of the method of this class:

```php
$factoryDefinition = [App\Services\Foo::class, 'methodName'];
```

To obtain the result of a call to a PHP "factory" class, the first step is to **retrieve an object** via the container's `get('App\Services\Foo')` method; only then is the **factory method** `methodName()` invoked.

It follows from this that a string value representing the name of a PHP class serves as a container identifier[^ContainerId].

Any container identifier may be specified as the name of a factory class, provided that the object retrieved from the container implements the factory method.

### Example of specifying a container identifier as a factory class name { #example }

Using the container identifier `'services.foo_special'`, you can retrieve an instance of the PHP class `\App\Services\Foo` that has been configured in a specific way.

You need to declare a dependency injection via the factory method `\App\Services\Foo::doBaz()` into the `$value` constructor parameter of the PHP class `\App\Services\Bar`.

```php
<?php
declare(strict_types=1);

namespace App\Services;

final class Bar
{
    public function __construct(public readonly mixed $value) {}    
}
```

The configuration file `custom_services.php` defines a specific configuration for the PHP class `\App\Services\Foo` and a container identifier `'services.foo_special'`:

```php
<?php
declare(strict_types=1);

// file: /app/config/custom_services.php

use App\Services\Foo;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield 'services.foo_special' => diAutowire(Foo::class, isSingleton: true)
        ->setup('methodOne')
        ->setup('methodTwo');
};
```

#### Declaration in configuration files { #example-php-definition }

In the `services.php` configuration file, register the PHP class `\App\Services\Bar` in the container and configure dependency injection for the constructor parameter `$value` using the [`diFactory()`](../php-definition/di-factory.md) helper function.

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php  // [!code highlight]

use App\Service\Bar;

use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindArguments(
            value: diFactory(['services.foo_special', 'doBaz'])//[!code ++]        
        );
};
```

#### Declaration via an attribute { #example-attribute }

Declare the injection of a value into the `$value` constructor parameter of the PHP class `\App\Services\Bar` using the [`\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md) attribute.

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]

final class Bar
{
    public function __construct(
        #[DiFactory(['services.foo_special', 'doBaz'])]//[!code ++]
        public readonly mixed $value
    ) {}    
}
```

<!--@include: ../_include/term_notes.md-->
