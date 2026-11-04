# Параметры контейнера

## Обзор { #overview }

При объявлении PHP-класса или вызываемого типа[^CallableType], можно не указывать контейнеру как внедрять зависимости **простых типов**, указывая в методе `bindArguments()` [^BindArguments] значение «как-есть».

К **простым типам** относятся значения `int`, `float`, `string`, `bool`, `null`, `\UnitEnum` или тип `array` состоящий из этих типов.

Рассмотрим пример: необходимо обеспечить внедрение зависимостей в PHP-классы из простых типов, указывая значения зависимостей «как-есть» через метод `bindArguments()`.

PHP-классы:

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        private readonly string $adminEmail
    ) {}

    //…
}
```

```php [Configurator.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Configurator.php //[!code highlight]

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
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

:::

Объявление внедрений простых параметров в файле конфигураций:

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php  //[!code highlight]

use App\Services\{Foo, Configurator};

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

Как можно видеть в файле конфигурации используется одно и то же значение `'admin@example.com'` для внедрения.

Повторяющиеся значение можно определить как «**параметр контейнера**», который представляет собой многократно используемое значение при объявлении внедрений в параметры.

**Конфигурацию параметров контейнера можно представить в виде коллекции <nobr>ключ–значение</nobr>: где ключ это строковое имя параметра, а значение представлено одним из [поддерживаемых типов](#parameter-support-types).**

Выше приведенный пример, где значение для параметра метода класса можно представить как «параметр контейнера»:

```php
<?php
declare(strict_types=1);

// file: /app/config/parameters.php //[!code highlight]

return [
    'adminEmail' => 'admin@example.com',
];
```

[Хелпер функция `diParameter()`](inject_params.md#helper-fn-di-parameter) внедряет зависимость из «параметров контейнера»:

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php  //[!code highlight]

use App\Services\{Foo, Configurator};

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

Для объявления параметров контейнера используем [метод `DiContainerBuilder::loadParameters()`](declare_params.md):

```php
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

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
- PHP-константы из выше перечисленных типов определяемые через функцию [`\define()`](https://www.php.net/manual/en/function.define.php)
- значения из публичных констант PHP-классов и PHP-интерфейсов со значениями из выше перечисленных типов.

```php
<?php
declare(strict_types=1);

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
<!--@include: ../_include/term_notes.md-->