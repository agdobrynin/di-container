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
// файл /app/config/parameters.php
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

---------------------
🚧🚧🚧🚧



## Параметры контейнера определяемые во время выполнения { #parameter-runtime }
Некоторые параметры контейнера нельзя определить в конфигурационных файлах,
поскольку значение параметра вычисляется во время выполнения с использованием зависимостей контейнера.

Для [компилируемого контейнера](../00-container-builder.md#компиляция-контейнера) важно дать знать о будущем существовании «параметра контейнера».
Для таких случаев есть определение «параметра контейнера времени исполнения».

### Хелпер функция diParameterRuntime.
Хелпер функция `diParameterRuntime` используется при [конфигурировании контейнера как php определений](../03-php-definition.md#diparameterruntime).

### PHP атрибут ParameterRuntime.
Php атрибут `ParameterRuntime` необходимо использовать при [конфигурации определений через PHP атрибуты](../02-attribute-definition.md#parameterruntime).

### Пример использования «параметра контейнера времени исполнения».
```php
namespace App\Services;

use Kaspi\DiContainer\Attributes\ParameterRuntime;

final class Foo {
    public function __construct(private string $qux) {}
}

final class Bar {
    public function __construct(
        #[ParameterRuntime('baz')]
        private string $qux
    ) {}
}
```
```php
use App\Services\{Foo, Bar};
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', src: '/app/src/')
    ->load('/app/config/services.php')
    // компиляция контейнера
    ->compileToFile('/app/var/cache/', 'App\AppContainer')
    ->build()
;

// некая функция `doCalculate()` вычисляет значение
// и возвращает строку `'random_string'`  
$calculatedString = doCalcualte();

// 🚩 Установка параметра контейнера 'foo' и его значения
// в уже сформированный контейнер зависимостей (runtime container)
$container->parameters()
    ->set('baz', $calculatedString);

$fooClass = $container->get(Foo::class);
$barClass = $container->get(Bar::class);
```
> [!NOTE]
> При разрешении параметров конструктора класса `App\Services\Foo`
> в свойстве `App\Services\Foo::$qux` будет значение `'random_string'`
> полученное на основе конфигурации через хелпер функцию `diParameterRuntime`.
> 
> При разрешении параметров конструктора класса `App\Services\Bar`
> в свойстве `App\Services\Bar::$qux` будет значение `'random_string'`
> полученное на основе конфигурации через PHP атрибут `ParameterRuntime`.



<!--@include: ../php-definition/_include/bind_notes.md-->