---
aside: false
---
# Сброс состояния объекта через рефлексию объекта { #title }

Для некоторых сценариев использования контейнера может быть полезным знать как использовать возможность сброса состояния объекта когда невозможно получить доступ к приватным и защищённым свойствам PHP-класса напрямую, **например в PHP-классе от стороннего производителя**.

Механизм сброса состояния объекта в контейнере получает объект с внедрёнными зависимостями через метод `get()` и его идентификатор[^ContainerId]. Для получения доступа к приватным и защищённым свойствам объекта используется [рефлексия (Reflection) в PHP](https://www.php.net/manual/en/book.reflection.php) – это встроенный API, позволяющий программе анализировать собственную структуру во время выполнения.

Рассмотрим пример сброса состояния объекта через рефлексию PHP-класса который предоставлен из пакета стороннего производителя `\VendorName\MetricCollector`.

```php
<?php
declare(strict_types=1);

// file: /vendor/VendorName/src/Foo.php [!code highlight]

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

PHP-класс `\VendorName\MetricCollector` необходимо объявить в контейнере чтобы сохранял своё состояние между компонентами системы, т.е. получать один и тот же объект при внедрении зависимости или получении через метод контейнера `get()`. Объявление этого класса сделаем через [хелпер функцию `diAutowire()`](../php-definition/di-autowire.md) и передадим аргумент `true` в параметр функции `isSingleton`.

В приведённом примере невозможно напрямую получить доступ к свойству `$metrics` или изменить код PHP-класса `\VendorName\MetricCollector`. Для сброса состояния объекта мы можем использовать [PHP-класс `\ReflectionProperty`](https://www.php.net/manual/en/class.reflectionproperty.php).

Сделаем объявление значения для сброса состояния объекта через анонимную функцию с использованием рефлексии: 

```php
<?php
declare(strict_types=1);

// file: /app/config/services_from_vendor_name.php

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