---
outline: [2,4]
title: Inject lazy objects by the container
---
### Inject lazy objects by the container { #title }

Lazy dependency injection allows for deferring the creation of an object's dependencies until the moment they are actually used—and not a moment sooner. To implement this functionality, the container utilizes "Virtual Proxies," as described in the PHP documentation [here](https://www.php.net/manual/en/language.oop5.lazy-objects.php).

> [!IMPORTANT]
> The mechanism for implementing lazy dependencies **is available only for PHP 8.4 and higher**.

> [!TIP]
> **If the PHP version is below 8.4**, you can declare a lazy dependency injection using the [`diProxyClosure()`](../php-definition/di-proxy-closure.md) helper function or the [`ProxyClosure()`](../attribute-definition/proxy-closure.md) attribute.

### How to use { #how-to-use }

Imagine that the PHP class `\App\Bar` requires significant computational resources and memory during initialization, thereby increasing the time it takes to create an object of that class.

The container will inject the PHP class `\App\Bar` into the constructor of the PHP class `\App\Foo`.

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

As the code shows, the constructor of the PHP class `\App\Foo` will receive two injected objects via the container whenever `$container->get(App\Foo::class)` is called, including the resource intensive class `\App\Bar`, even though it is not always used.

Declaring a lazy injection for the PHP class `\App\Bar` resolves the issue of instantiating resource intensive dependencies.
 
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

The initialization of the `\App\Bar` PHP class, configured as a lazy injection, will occur only when the private property `$bar` of the `\App\Foo` class is accessed specifically, when the `\App\Foo::doWithBar()` method is called and not before. This reduces memory consumption, resource usage, and the time required to initialize the `\App\Foo` PHP class.


### Section Contents { #toc }

- [Global declaration of a lazy PHP class in a container](inject_as_lazy_global.md)
- [Declaring the injection of a lazy PHP class into a parameter](inject_lazy_arg.md)
