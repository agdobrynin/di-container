---
outline: [2, 5]
title: Global declaration of interface implementation
---
### Example of a global interface implementation declaration { #overview }

All typed parameters of a method or a callable type[^CallableType] must implement the same PHP class, which is declared as an implementation of a specific interface.

### PHP classes { #php-classes }

::: code-group
<<< @/_code/cookbook/inject-interface/src/Services/Foo.php
<<< @/_code/cookbook/inject-interface/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/inject-interface/src/Services/Bar.php
<<< @/_code/cookbook/inject-interface/src/Services/Baz.php
:::

You need to register the `\App\Services\Bar` PHP class as the implementation of the `\App\Interfaces\QuxInterface` interface. The container should automatically inject this registered implementation into method parameters, without requiring any additional declarations.

Note that while the `\App\Services\Baz` PHP class also implements `\App\Interfaces\QuxInterface`, it will not be injected into any parameter without an explicit declaration.

#### Helper functions { #helper-fn-global }

For this task, you need to declare the PHP class `\App\Services\Bar` in the [configuration file](../../documentation/container-builder/configuration_files.md) and specify the interface's FQCN[^FQCN] as the container identifier[^ContainerId].

Below, we will look at ways to declare an interface implementation using various helper functions.

---

##### diAutowire()

The `services.php` configuration file uses the [`diAutowire()`](../../documentation/php-definition/di-autowire.md) helper function to declare a PHP class, with the `\App\Interfaces\QuxInterface` interface specified as the container identifier.

> [!NOTE]
> This is a simple example of a declaration; however, when declaring a PHP class, all methods of this helper function are available—including those for [defining constructor injection](../../documentation/php-definition/di-autowire.md#bindarguments) and injection via [setter methods](../../documentation/php-definition/di-autowire.md#di-autowire-setter-method).

::: code-group
<<< @/_code/cookbook/inject-interface/container.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Interfaces\QuxInterface;
use App\Service\Bar;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield QuxInterface::class => diAutowire(Bar::class);//[!code ++]
};
```
:::

<!--@include: _include/inject-interface-result.md-->

---

##### diCallable()

When declaring an interface implementation, the construction of a PHP class may be complex; therefore, this process can be delegated to any callable type[^CallableType].

The `services.php` configuration file uses the [`diCallable()`](../../documentation/php-definition/di-callable.md) helper function to define a PHP class via an anonymous function, with the `\App\Interfaces\QuxInterface` interface serving as the container identifier.

Additionally, in this interface implementation declaration, the `$isSingleton` parameter of the helper function is set to `true`, ensuring that the container retrieves the same object instance rather than creating a new one each time.

::: code-group
<<< @/_code/cookbook/inject-interface/container.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Interfaces\QuxInterface;
use App\Service\Bar;

use function Kaspi\DiContainer\diCallable;

return static function (): Generator {
    // Сложная сборка PHP-класса
    $fn = static function (): Bar {//[!code highlight]
        $bar = new Bar();//[!code highlight]
        // [!code highlight]
        // тут возможны дополнительные действия с $bar [!code highlight]
        // [!code highlight]
        return $bar;//[!code highlight]
    };//[!code highlight]

    yield QuxInterface::class => diCallable($fn, isSingleton: true);//[!code ++]
};
```
:::

<!--@include: _include/inject-interface-result.md-->

> [!TIP]
> A similar declaration can be made using the helper function [`diFactory()`](../../documentation/php-definition/di-factory.md).

---

##### diGet()

If a PHP class definition—complete with all necessary injections and configurations—already exists within the container, you can simply reference the existing container identifier for that PHP class when declaring an implementation of an interface.

The `services.php` configuration file uses the helper function [`diGet()`](../../documentation/php-definition/di-get.md) to register a PHP class as an implementation of the `\App\Interfaces\QuxInterface` interface.

::: code-group
<<< @/_code/cookbook/inject-interface/container.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Interfaces\QuxInterface;
use App\Service\Bar;

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Bar::class);//[!code highlight]
    
    // … many lines
    
    yield QuxInterface::class => diGet(Bar::class);//[!code ++]
};
```
:::

<!--@include: _include/inject-interface-result.md-->

---

#### Attributes { #attributes-global }

The [`\Kaspi\DiContainer\Attributes\Service`](../../documentation/attribute-definition/service.md) attribute allows you to globally register a specific PHP class as the implementation of an interface within the container. This means the container will automatically inject this registered implementation whenever a dependency on that interface is required — unless a different implementation has been explicitly specified for a particular method parameter or type being invoked.

##### Service and FQCN { #service-fqcn }

You need to add an attribute to the `\App\Interfaces\QuxInterface` interface and specify its implementation via the PHP class `\App\Services\Bar`.

The `$id` parameter of the attribute’s constructor accepts a string value that tells the container which identifier to use to retrieve an object implementing this interface; in this case, you can provide the value `'App\Services\Bar'` or use the constant `\App\Services\Bar::class`.

::: code-group
```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php

namespace App\Interfaces;

use App\Service\Bar;//[!code ++]
use Kaspi\DiContainer\Attributes\Service;//[!code ++]

#[Service(Bar::class)]//[!code ++]
interface QuxInterface
{
    // …
}
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_attr.php [container.php]
:::

<!--@include: _include/inject-interface-result.md-->

---

##### Service and its significance for the $id parameter { #service-container-id }

The documentation for the [`\Kaspi\DiContainer\Attributes\Service`](../../documentation/attribute-definition/service.md) attribute states that the `$id` constructor parameter must serve as a container identifier. This means that if an object implementing the declared interface can be retrieved via the container's `get()` method, then that value is valid for the `$id` parameter.

Consider an example where an implementation of an interface is retrieved using the container identifier `'services.bar'`, which is declared for the PHP class `\App\Services\Bar` via the [`\Kaspi\DiContainer\Attributes\Autowire`](../../documentation/attribute-definition/autowire.md) attribute.

Declaring an interface implementation in this way can be useful when the same PHP class needs to be registered in the container multiple times – each instance having different injected dependencies and a unique container identifier.

::: code-group
```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php

namespace App\Interfaces;

use App\Service\Bar;//[!code ++]
use Kaspi\DiContainer\Attributes\Service;//[!code ++]

#[Service('services.bar')]//[!code ++]
interface QuxInterface
{
// …
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(id: 'services.bar')]//[!code ++]
final class Bar implements QuxInterface
{
// …
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_attr.php [container.php]
:::

<!--@include: _include/inject-interface-result.md-->

<!--@include: ../../documentation/_include/term_notes.md-->
