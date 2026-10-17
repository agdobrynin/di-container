# Параметры контейнера

## Обзор { #overview }

При конфигурировании PHP класса или вызываемого типа `callable`, можно не указывать контейнеру как внедрять зависимости **простых типов**, указывая в методе `bindArguments()` [^BindArguments] значение «как-есть».

К **простым типам** относятся значения `int`, `float`, `string`, `bool`, `null`, `\UnitEnum` или тип `array` состоящий из простых типов.

Необходимо обеспечить внедрение зависимостей в PHP классы из простых типов, указывая значения зависимостей «как-есть» через метод `bindArguments()`.

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo {
    public function __construct(
        private readonly string $adminEmail
    ) {}

    //…
}
```

```php [Configurator.php]
// file: /app/src/Services/Configurator.php
namespace App\Services;

final class Configurator
{
    public function __construct(private readonly Bar $bar) {}

    public static function __invoke(string $adminEmail): mixed
    {
        // …
    }
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

:::

Конфигурирование в файле:

```php
// file: /app/config/services.php
use App\Services\{Foo, Configurator};
use Generator;

use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            // значение как-есть// [!code highlight]
            adminEmail: 'admin@example.com'// [!code highlight]
        );

    yield 'services.do_something' => diFactory(Configurator::class)
        ->bindArguments(
            // значение как-есть// [!code highlight]
            adminEmail: 'admin@example.com'// [!code highlight]
        );
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->import('App\\', src: '/app/src/')
    ->build();
```

Как можно видеть в файле конфигурации используется одно и то же значение `'admin@example.com'` для внедрения.

Иногда одно и то же значение внедряется в параметры методов PHP классов или вызываемых типов `callable`, в нескольких конфигурационных файлах. Повторяющиеся значение можно определить как «**параметр контейнера**», который представляет собой многократно используемое значение при конфигурировании внедрений в параметры.

**Конфигурацию параметров контейнера можно представить в виде коллекции <nobr>ключ–значение</nobr>: где ключ это строковое имя параметра, а значение представлено одним из [поддерживаемых типов](#parameter-support-types).**

Выше приведенный пример, где значение для параметра метода класса можно представить как «параметр контейнера»:

```php
// file: /app/config/parameters.php
return [
    'adminEmail' => 'admin@example.com',
];
```

[Хелпер функция `diParameter()`](inject_params.md) внедряет зависимость из «параметров контейнера»:

```php
// file: /app/config/services.php
use App\Services\{Foo, Configurator};
use Generator;

use function Kaspi\DiContainer\{diAutowire, diFactory, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            adminEmail: 'admin@example.com'// [!code --]
            adminEmail: diParameter('adminEmail')// [!code ++]
        );
        
    yield 'services.do_something' => diFactory(Configurator::class)
        ->bindArguments(
            adminEmail: 'admin@example.com'// [!code --]
            adminEmail: diParameter('adminEmail')// [!code ++]
        );
};
```

Используем метод `DiContainerBuilder::loadParameters()` для загрузки конфигурации «параметров контейнера»:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')// [!code ++]
    ->load('/app/config/services.php')
    ->import('App\\', src: '/app/src/')
    ->build();
```

## Поддерживаемые типы значений параметров контейнера { #parameter-support-types }

В параметры контейнера можно установить следующие простые типы значений:
- скалярные php типы `int|float|string|bool`
- тип `null`
- перечисляемые типы наследуемые от `\UnitEnum`
- `array` из выше перечисленных типов
- PHP константы из выше перечисленных типов определяемые через [`\define()`](https://www.php.net/manual/en/function.define.php)
- значения из публичных констант у php классов и php интерфейсов со значениями из выше перечисленных типов.

```php
// конфигурация параметров контейнера.

return [
    // строковое значение
    'adminEmail' => 'admin@example.com',
    // булевы тип
    'services.enable_logging' => true,
    // числовой тип - целое число, число с плавающей точкой
    'logger.port' => 1_025,
    // тип null
    'smtp.context' => null,
    // массив
    'ui.locales' => [
        'ru' => 'ru_RU.utf8',
        'en' => 'en_US.utf8',
        'fi' => 'fi_FI.utf8',
    ],
    // значение установленное из PHP константы определенное через `\define()`
    'value.from_constat' => CONSTATN_NAME,
    // значение установленное через публичную константу в классе
    'value.from_class_constat' => Foo::MAX_SIZE,
    // значение установленное через перечисления наследуемые от `\UnitEnum`
    'value.from_enumerator' => FooEnum::Viewed,
];
```

## Содержание раздела { #toc }

- [Объявление «параметров контейнера»](declare_params.md).
- [Ссылки на имена других «параметров контейнера»](expression_in_params.md).
- [Внедрение «параметров контейнера»](inject_params.md).
- [«Параметры контейнера» времени выполнения](inject_runtime_params.md).

<!--@include: ../php-definition/_include/bind_notes.md-->