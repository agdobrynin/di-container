---
aside: false
title: Resetting an object's state via object reflection
---
### Resetting an object's state via object reflection { #title }

For certain container use cases, it can be helpful to know how to reset an object's state when it is impossible to directly access the private or protected properties of a PHP class for instance, when dealing with a class from a third-party vendor.

The object state reset mechanism within the container retrieves an object along with its injected dependencies via the `get()` method and its corresponding identifier[^ContainerId]. To access the object's private and protected properties, the mechanism of [PHP Reflection](https://www.php.net/manual/en/book.reflection.php) a built-in API that allows a program to analyze its own structure at runtime.

Let’s look at an example of how to reset the state of an object specifically an instance of the `\VendorName\MetricCollector` class from a third-party package using reflection.

```php
<?php
declare(strict_types=1);

// file: /vendor/VendorName/src/MetricCollector.php [!code highlight]

namespace VendorName;


final class MetricCollector
{
    private array $metrics = [];
    
    public function addMetric(string $key, mixed $value): void
    {
        $this->metrics[$key] = $value;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }   
}
```

The PHP class `\VendorName\MetricCollector` must be registered in the container in a way that preserves its state across system components ensuring that the same object instance is retrieved whenever it is injected as a dependency. We will register this class using the helper function [`diAutowire()`](../php-definition/di-autowire.md), passing `true` to the `$isSingleton` parameter.

In an application with long-running processes, using the same object instance will lead to an accumulation of data within the `$metrics` property of the `\VendorName\MetricCollector` class.

Since it is not possible to directly access the `$metrics` property or modify the source code of the `\VendorName\MetricCollector` class in this scenario, we will use the PHP class [`\ReflectionProperty`](https://www.php.net/manual/en/class.reflectionproperty.php) to reset the object's state.

We will define the logic for resetting the object's state using an anonymous function that leverages reflection:

```php
<?php
declare(strict_types=1);

// file: /app/config/services_from_vendor_name.php [!code highlight]

use VendorName\MetricCollector;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(MetricCollector::class, isSingleton: true)
        ->setResetter(static function (MetricCollector $collector): void {//[!code ++]
            $propertyMetrics = new ReflectionProperty($collector, 'metrics');//[!code ++]
            $propertyMetrics->setValue($collector, []);//[!code ++]
        });//[!code ++]
};
```

<!--@include: ../_include/term_notes.md-->