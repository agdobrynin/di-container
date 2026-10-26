# ProxyClosure

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\ProxyClosure` внедряет «ленивую» зависимость через встроенный PHP класс [`\Closure`](https://www.php.net/manual/en/class.closure.php), откладывая инициализацию зависимости до момента фактического его использования.

Такой приём объявления зависимости пригодится для внедрения «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмкий вычислений.

> [!IMPORTANT]
> Обязательно типизировать параметр[^TypeHints] как `\Closure` или `callable` для внедрения зависимости через атрибут `\Kaspi\DiContainer\Attributes\ProxyClosure`.
> ```php{2}
>  public function __construct(
>       private readonly \Closure $bar
>  ) {}
> ```

> [!TIP]
> Если используется PHP 8.4 и выше, то вместо атрибута `\Kaspi\DiContainer\Attributes\ProxyClosure` предпочтительнее внедрять зависимость через встроенный в PHP механизм [«ленивых объектов»](../14-lazy-injection.md).

Конфигурация атрибута:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\ProxyClosure::__construct(
    string $containerIdentifier
)
```

Параметры:
- `$containerIdentifier` – идентификатор контейнера [^ContainerId].

## Пример внедрения параметра с отложенной инициализацией { #example }

PHP класс `\App\Services\Bar` при инициализации производит ресурсоёмкие вычисления. Необходимо внедрить `\App\Services\Bar` в конструктор класса `\App\Services\Foo` используя приём «ленивой» инициализации объекта через встроенный в PHP класс `\Closure`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Closure;
use Kaspi\DiContainer\Attributes\ProxyClosure;//[!code ++]

final class Foo
{
    /**
     * @param Closure(): Bar $barProxy
     */
    public function __construct(
        #[ProxyClosure(Bar::class)]//[!code ++]
        private readonly Closure $barProxy
    ) {}

    public function getBar(): Bar
    {
        return ($this->barProxy)();
    }
}
```

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->getBar() instanceof Bar);
// bool(true)
```

<<< @/_code/php-definition/di-proxy-closure/Bar.php

:::

> [!TIP]
> Используйте [PhpDocBlock](https://docs.phpdoc.org/guide/getting-started/what-is-a-docblock.html) для подсказок и автодополнения в IDE:
> ```php{1-3}
>   /**
>    * @param Closure(): Bar $barProxy
>    */
>  public function __construct(private readonly Closure $barProxy) {}
> ```

<!--@include: ../_include/term_notes.md-->
