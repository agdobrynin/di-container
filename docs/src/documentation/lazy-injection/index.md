---
outline: [2,4]
---
# Внедрение «ленивых» объектов контейнером { #title }


## Обзор { #overview }

Ленивое внедрение зависимостей позволяет отложить создание зависимостей объекта до момента их фактического использования, а не раньше. Для реализации этого функционала контейнера используется [«Virtual Proxies» описанный в PHP документации](https://www.php.net/manual/ru/language.oop5.lazy-objects.php).

> [!IMPORTANT]
> Механизм внедрения ленивых зависимостей **доступен только для PHP 8.4 и выше**.

> [!TIP]
> **Если версия PHP ниже 8.4**, то можно объявить внедрение «ленивой» зависимости через [хелпер функцию `diProxyClosure()`](../php-definition/di-proxy-closure.md) или [атрибут `ProxyClosure()`](../attribute-definition/proxy-closure.md).

## Как использовать { #how-to-use }

Представьте что PHP-класс `\App\Bar` при инициализации требует много вычислительных ресурсов и памяти, тем самым увеличивая время создания объекта такого класса.

В конструкторе PHP-класса `\App\Foo` объявлено внедрение PHP-класса `\App\Bar`, так как он требуется для некоторых вычислений в классе `\App\Foo`, при том что используется он не всегда.

```php
<?php
declare(strict_types=1);

namespace App;

final class Foo
{
    public function __construct(
        private readonly Bar $bar,
        private readonly Baz $baz,
    ) {}
    
    public function doWithBar(): mixed//[!code warning]
    {//[!code warning]
        // using the `$this->bar` dependency here [!code warning]
    }//[!code warning]
    
    public function doWithBaz(): mixed//[!code highlight]
    {//[!code highlight]
        // using the `$this->baz` dependency here [!code highlight]
    }//[!code highlight]
}
```

Как видно из кода, конструктор PHP-класса `\App\Foo` получит через контейнер внедрение двух объектов при любом вызове `$container->get(App\Foo::class)`, в том числе ресурсоёмкий класс `\App\Bar`, но зависимости, внедрённые через конструктор, требуется не во всех методах класса.

Зависимость PHP-класса `\App\Bar` требуется только при вызове метода `\App\Foo::doWithBar()`, поэтому объявление ленивого внедрения PHP-класса `\App\Bar` решает проблему получения ресурсоёмких внедрений. Инициализация ленивого внедрения PHP-класса `\App\Bar` произойдет только в момент обращения к приватному свойству `$bar` класса `\App\Foo`, т.е при обращении к методу `\App\Foo::doWithBar()` а не раньше, тем самым уменьшая потребление памяти, ресурсов и времени инициализации PHP-класса `\App\Foo`.

 
<!--@include: _include/lazy_declare.md-->

```php
<?php
declare(strict_types=1);

namespace App;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

final class Foo
{
    public function __construct(
        #[Autowire(isLazy: true)]//[!code ++]
        private readonly Bar $bar,
        private readonly Baz $baz,
    ) {}
    
    public function doWithBar(): mixed
    {
        // using the `$this->bar` dependency here [!code warning]
    }
    
    public function doWithBaz(): mixed
    {
        // using the `$this->baz` dependency here [!code highlight]
    }
}
```


## Содержание раздела { #toc }

- [Глобальное объявление ленивого PHP-класса в контейнере](inject_as_lazy_global.md)
- [Объявление внедрения ленивого PHP-класса в параметр](inject_lazy_arg.md)
