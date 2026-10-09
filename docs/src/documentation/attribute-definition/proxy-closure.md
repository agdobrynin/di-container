# ProxyClosure

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\ProxyClosure` внедряет зависимость как «ленивую» через встроенный PHP класс \Closure, откладывая инициализацию зависимости до момента фактического его использования.

Такой приём объявления зависимости пригодится для внедрения «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмкий вычислений.

> [!IMPORTANT]
> Внедряя зависимость через атрибут `\Kaspi\DiContainer\Attributes\ProxyClosure` необходимо указать type hints [^TypeHints] параметра как `\Closure` или `callable`.

> [!TIP]
> Если используется PHP 8.4 и выше, то предпочтительнее использовать [конфигурирование «ленивых объектов»](../14-lazy-injection.md) вместо атрибута `\Kaspi\DiContainer\Attributes\ProxyClosure`.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\ProxyClosure::__construct(
    string $containerIdentifier
)
```

Параметры:
- `$containerIdentifier` – FQCN [^FQCN], или идентификатор контейнера.

## Пример внедрения параметра с отложенной инициализацией { #example }

Классы для конфигурирования:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use Closure;
use Kaspi\DiContainer\Attributes\ProxyClosure;

class Foo
{
    /**
    * 🚩 Подсказка для IDE при авто-дополении (autocomplete).
    * 
    * @param Closure(): Bar $barProxy
    */
    public function __construct(
        #[ProxyClosure(Bar::class)]
        private readonly Closure $barProxy
    ) {}

    public function getBar(): Bar
    {
        // Выполнить замыкание
        return ($this->barProxy)();
    }
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

class Bar {
    // Класс требующий длительного времени инициализации
    // и ресурсоёмкий вычислений
}
```

:::

Контейнер зависимостей:

```php
use App\Services\{Foo, Bar};
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

// PHP класс App\Services\Bar еще не инициализирован контейнером,
// ресурсы – память и процессорное время не затрачены
// для инициализации PHP класса

var_dump($foo instanceof Foo);
// bool(true)

// Контейнер зависимостей начал выполнять
// инициализацию PHP класса App\Services\Bar
// затрачивая память и процессорное время. 
$bar = $foo->getBar();

var_dump($bar instanceof Bar);
// bool(true)
```

<!--@include: ../_include/term_notes.md-->
