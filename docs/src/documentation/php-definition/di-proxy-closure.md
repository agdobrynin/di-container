---
outline: [2, 4]
---
# diProxyClosure()

## Обзор { #overview }

Хелпер функция `diProxyClosure()` внедряет зависимость как «ленивую» через встроенный PHP класс `\Closure`,
откладывая инициализацию зависимости до момента фактического его использования.

Такой приём объявления зависимости пригодится для внедрения «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмкий вычислений.

> [!IMPORTANT]
> Обязательно типизировать параметр[^TypeHints] как `\Closure` или `callable` для внедрения зависимости через хелпер функцию `diProxyClosure()`.
> ```php{2}
>  public function __construct(
>       private readonly \Closure $bar
>  ) {}
> ```

> [!TIP]
> Если используется PHP 8.4 и выше, то вместо хелпер функции `diProxyClosure()` предпочтительнее внедрять зависимость через встроенный в PHP механизм [«ленивых объектов»](../14-lazy-injection.md).

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

\Kaspi\DiContainer\diProxyClosure(
    string $containerIdentifier,
    ?bool $isSingleton = null,
): DiDefinitionTagArgumentInterface
```

Параметры:
- `$containerIdentifier` – FQCN [^FQCN], или идентификатор контейнера [^ContainerId].
- `$isSingleton` – возвращать один и тот же результат (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

Функция `diProxyClosure()` возвращает объект предоставляющий методы:
- `bindTag()` – добавляет тег с метаданными для определения.

## bindTag()

Теги позволяют отнести «ленивое» определение к коллекции элементов отмеченных одним тегом.

<!--@include: ./_include/bind_tag.md-->

## Идентификатор контейнера { #container-id }

Чтобы обеспечить доступность «ленивого» определения через метод контейнера `get()` и `has()` необходимо указать в файле конфигураций идентификатор контейнера[^ContainerId] соответствующий объявленному определению.

::: code-group

```php [lazy_entries.php]
<?php
declare(strict_types=1);

// file: /app/config/lazy_entries.php // [!code highlight]

use App\Services\Bar;

use function Kaspi\DiContainer\diProxyClosure;

return static function(): Generator {
    yield 'services.bar_lazy' => diProxyClosure(// [!code ++]
        Bar::class,// [!code ++]
        isSingleton: true// [!code ++]
    );// [!code ++]
};
```

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Bar;

$container = (new DiContainerBuilder())
    ->load('/app/config/lazy_entries.php')//[!code highlight]
    ->build();

$barLazy = $container->get('services.bar_lazy');

var_dump($barLazy instanceof Closure);
// bool(true)

var_dump(($barLazy)() instanceof Bar);
// bool(true)
```

<<< @/_code/php-definition/di-proxy-closure/Bar.php

:::

## Пример внедрения параметра с отложенной инициализацией { #example }

PHP класс `\App\Services\Bar` при инициализации производит ресурсоёмкие вычисления. Необходимо внедрить `\App\Services\Bar` в конструктор класса `\App\Services\Foo` используя приём «ленивой» инициализации объекта через встроенный в PHP класс `\Closure`.

::: code-group 

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diProxyClosure};

return static function(): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(// [!code ++]
            barProxy: diProxyClosure(Bar::class)// [!code ++]
        );// [!code ++]
};
```

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->getBar() instanceof Bar);
// bool(true)
```

<<< @/_code/php-definition/di-proxy-closure/Foo.php

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
