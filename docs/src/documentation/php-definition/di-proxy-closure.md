---
outline: [2, 4]
---
# diProxyClosure

## Обзор { #overview }

Функция `diProxyClosure()` откладывает инициализацию получения зависимости из контейнера до момента фактического его использования,
делая загрузку «ленивой», через встроенный PHP класс `\Closure`.

Такой приём объявления зависимости пригодится для внедрения «тяжёлых» зависимостей, требующих длительного времени инициализации или ресурсоёмкий вычислений.

> [!IMPORTANT]
> Внедряя зависимость через хелпер функцию `diProxyClosure()` в параметр метода или вызываемого типа `callable` следует помнить
> что тип параметра должен быть объявлен как `\Closure`.

> [!TIP]
> Если используется PHP 8.4 и выше, то предпочтительнее использовать [конфигурирование «ленивых объектов»](../14-lazy-injection.md) вместо хелпер функции `diProxyClosure()`.

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

\Kaspi\DiContainer\diProxyClosure(
    string $containerIdentifier,
    ?bool $isSingleton = null,
): DiDefinitionTagArgumentInterface
```

Параметры:
- `$containerIdentifier` – идентификатора контейнера, php класс реализующий сервис который необходимо разрешить отложено.
- `$isSingleton` – возвращать один и тот же результат (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

Функция `diProxyClosure()` возвращает объект предоставляющий методы:
- `bindTag()` – добавляет тег с мета-данными для определения.


### Пример для отложенной инициализации сервиса как аргумента { #example }

Конфигурирование:

```php
// /app/config/services.php

use App\Services\{Foo, Bar};
use function Kaspi\DiContainer\{diAutowire, diProxyClosure};

return static function(): \Generator {

    yield diAutowire(Foo::class)
        ->bindArguments(
            barProxy: diProxyClosure(Bar::class),
        );

};
```

Контейнер зависимостей:

```php
use App\Services\{Foo, Bar};
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build()
;

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

Классы для конфигурирования:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use Closure;

class Foo
{
    /**
    * 🚩 Подсказка для IDE при авто-дополении (autocomplete).
    * 
    * @param Closure(): Bar $barProxy
    */
    public function __construct(private readonly Closure $barProxy) {}

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

## bindTag()

Теги позволяют отнести конфигурируемый вызываемый тип к коллекции сервисов.

<!--@include: ./_include/bind_tag.md-->

## Идентификатор контейнера для `diProxyClosure()` { #container-id }

Хелпер функция `diProxyClosure()` не может автоматически сформировать идентификатор контейнера,
поэтому необходимо указать идентификатор контейнера для определения конфигурируемого через эту хелпер функцию.

```php
// config/services.php
use App\Services\Bar;
use function Kaspi\DiContainer\diProxyClosure;

return static function(): \Generator {

    yield 'services.bar' => diProxyClosure(Bar::class, isSingleton: true)

};
```
