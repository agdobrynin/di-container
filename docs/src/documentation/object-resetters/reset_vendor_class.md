---
aside: false
title: Сброс состояния объекта через рефлексию объекта
---
### Сброс состояния объекта через рефлексию объекта { #title }

Для некоторых сценариев использования контейнера может быть полезным знать как использовать возможность сброса состояния объекта когда невозможно получить доступ к приватным и защищённым свойствам PHP-класса напрямую, **например в PHP-классе от стороннего производителя**.

Механизм сброса состояния объекта в контейнере получает объект с внедрёнными зависимостями через метод `get()` и его идентификатор[^ContainerId]. Для получения доступа к приватным и защищённым свойствам объекта используется [рефлексия (Reflection) в PHP](https://www.php.net/manual/ru/book.reflection.php) – это встроенный API, позволяющий программе анализировать собственную структуру во время выполнения.

Рассмотрим пример сброса состояния объекта PHP-класса `\VendorName\MetricCollector` из пакета стороннего производителя через рефлексию.

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

PHP-класс `\VendorName\MetricCollector` необходимо объявить в контейнере так, чтобы он сохранял своё состояние между компонентами системы, т.е. получать один и тот же объект при внедрении зависимости. Объявление класса сделаем через [хелпер функцию `diAutowire()`](../php-definition/di-autowire.md) и передадим аргумент `true` в параметр функции `$isSingleton`.

При получении одного и того же объекта в рамках приложения с долго-живущими процессами приведет к «накоплению» данных в свойстве `$metrics` PHP-класса `\VendorName\MetricCollector`.

В приведённом примере невозможно напрямую получить доступ к свойству `$metrics` или изменить код PHP-класса `\VendorName\MetricCollector`, поэтому для сброса состояния объекта будем использовать [PHP-класс `\ReflectionProperty`](https://www.php.net/manual/ru/class.reflectionproperty.php).

Сделаем объявление значения для сброса состояния объекта через анонимную функцию с использованием рефлексии: 

```php
<?php
declare(strict_types=1);

// file: /app/config/services_from_vendor_name.php [!code highlight]

use VendorName\MetricCollector;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(MetricCollector::class, isSingleton: true)
        ->setResetter(static function (MetricCollector $collector): void {//[!code highlight]
            $propertyMetrics = new ReflectionProperty($collector, 'metrics');//[!code ++]
            $propertyMetrics->setValue($collector, []);//[!code ++]
        });//[!code highlight]
};
```

<!--@include: ../_include/term_notes.md-->