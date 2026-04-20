# 📋 Параметры контейнера.

При конфигурации параметров метода php-класса или callable выражения можно указывать простые типы такие как,
`int`, `float`, `string`, `bool`, `null`, `\UnitEnum` или массив `array` состоящий их этих типов,
напрямую (_как-есть_) в методе `bindArguments()` реализованный хелпер функциями
[diAutowire()](01-php-definition.md#diautowire),
[diCallable()](01-php-definition.md#dicallable) и
[diFactory()](01-php-definition.md#difactory) без указания как разрешить зависимость:

```php
// /app/config/services_one.php
use App\Services\Foo;
use function Kaspi\DiContainer\diAutowire;

return function () {
    yield diAutowire(Foo::class)
        ->bindArguments(
            // передать строковое значение как-есть 
            'admin@example.com'
        );
};
```
```php
// /app/config/services_two.php
use App\Services\Bar;
use function Kaspi\DiContainer\{diAutowire, diGet};

return function () {
    yield diAutowire(Bar::class)
        ->bindArguments(
            diGet('qux.service'),
            // передать строковое значение как-есть
            'admin@example.com',
        );
};
```
```php
namespace App\Services;

final class Foo {
    public function __construct(private readonly string $adminEmail) {}
}

final class Bar {
    public function __construct(
        private readonly QuxInterface $service,
        private readonly string $adminEmail,
    ) {}
}
```
Иногда одно и то же значение в параметре метода php-класса или callable выражения
используется в нескольких конфигурационных файлах.
Повторяющиеся значение можно определить как «параметр контейнера», который представляет собой многократно
используемое значение при конфигурировании определений контейнера (_сервисов_).

**Конфигурацию параметров контейнера можно представить в виде коллекции
ключ-значение, где ключ это строковое имя параметра, а значение представлено одним из [поддерживаемых типов](#поддерживаемые-типы-значений-параметров-контейнера).**

Выше приведенный пример, где значение для параметра метода класса можно представить как параметр контейнера:
```php
// /app/config/parameters.php
return [
    'adminEmail' => 'admin@example.com',
];
```
```php
// /app/config/services.php
use App\Services\Foo;
use function Kaspi\DiContainer\{diAutowire, diParameter};

return function () {
    yield diAutowire(Foo::class)
        ->bindArguments(
            diParameter('adminEmail')
        );
        
    yield diAutowire(Bar::class)
        ->bindArguments(
            diGet('qux.service'),
            diParameter('adminEmail'),
        );
};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load(
        '/app/config/services_one.php',
        '/app/config/services_two.php',
    )
    ->import('App\\', src: '/app/src/')
    ->build()
;
```
> [!TIP]
> Подробное описание [хелпер функции `diParameter()`](#хелпер-функция-diparameter)

## Регистрация параметров.
Класс-строитель `\Kaspi\DiContainer\DiContainerBuilder` для сборки контейнера
предоставляет несколько методов для регистрации параметров контейнера:
- [загрузка из файлов](#регистрация-параметров-контейнера-из-файлов-конфигураций)
- [загрузка из коллекции](#регистрация-параметров-контейнера-из-коллекции)
- [установка значения параметра](#регистрация-одного-параметра-контейнера)
- [установка параметров контейнера в конфигураторе определений](#управление-параметрами-контейнера-в-конфигураторе-определений)

> [!IMPORTANT]
> Порядок загрузки конфигураций параметров важен – при совпадении имен параметров контейнера
> значение будет перезаписано более поздним вызовом в конфигурации.

### Регистрация параметров контейнера из файлов конфигураций.
```php
DiContainerBuilder::loadParameters(string $file, string ...$_): static
```
Параметры:
- `$file` – полный путь к файлу описывающий конфигурацию параметров.
- `$_` – дополнительные файлы конфигураций параметров контейнера.

> [!IMPORTANT]
> Файл конфигурации параметров контейнера должен начинаться с ключевого слова `return`
> и возвращать любой итерируемый тип или callable выражение которое также возвращает любой итерируемый тип.
>

### Регистрация параметров контейнера из коллекции.
```php
DiContainerBuilder::addParameters(iterable $params): static
```
Параметры:
- `$params` – коллекция ключ-значение конфигурации параметров.

### Регистрация одного параметра контейнера.
```php
DiContainerBuilder::setParameter(string $name, array|int|float|string|bool|null|\UnitEnum $value): static
```
Параметры:
- `$name` – имя параметра.
- `$value` – значение параметра.

### Управление параметрами контейнера в конфигураторе определений.
Регистрировать и управлять параметрами контейнера так же можно в [файлах конфигурации](08-definitions-configurator.md) определений контейнера.

> [!IMPORTANT]
> Все добавленные ранее параметры в конфигурацию могут быть замены новыми значениями если имя параметра совпадает.
>

Интерфейс `\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface` предоставляем методы для конфигурирования параметров контейнера:

- `DefinitionsConfiguratorInterface::loadParameters()` – загрузка из файлов.
- `DefinitionsConfiguratorInterface::addParameters()` – загрузка из коллекции.
- `DefinitionsConfiguratorInterface::setParameter()` – установка параметра и его значения.
- `DefinitionsConfiguratorInterface::removeParameter()` – удаление параметра по имени из конфигурации параметров.
- `DefinitionsConfiguratorInterface::hasParameter()` – проверка на доступность параметра по имени.

```php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {

    $configurator->loadParameters('/app/config/more_app_params.php');

    if (!$configurator->hasParameter('debug')) {
        $configurator->setParameter('baz', null);
    } else {
        $configurator->removeParameter('foo');
    }

    yield diAutowire(Foo::class)
        ->bindArguments(
            diParameter('baz')
        )
    ;

};
```

## Поддерживаемые типы значений параметров контейнера.

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

## Ссылки на другие параметры в конфигурации параметров контейнера.
Чтобы указать ссылку на другой ранее добавленный параметр контейнера,
необходимо имя параметра указать между символами `{` и `}`.

Значение `'{emails.admin}'` будет интерпретировано как получение значения
параметра контейнера с именем `'emails.admin'`.

Для параметра типа `string` поддерживается синтаксис объединения строковых значений.
Значение `'{storage}/images'` будет интерпретировано как объединение значения параметра контейнера с именем `'storage'` и
части строки `'/images'`.

```php
// /app/config/app_params.php
return [
    'storage' => '/var/storage',
    'emails.admin' => 'admin@example.com',
    'emails.manager' => 'manager@example.com',
];
```
```php
// /app/config/dev_params.php
return [
    // будет приведено к виду `'/var/storage/images'`
    'storage.images' => '{storage}/images',
    // будет приведено к виду `'admin@example.com, manager@example.com'`
    'emails.as_string' => '{emails.admin}, {emails.manager}',
    // будет приведено к виду
    // `array(0 => 'admin@example.com', 1 => 'manager@example.com')`
    'emails.as_array' => [
        '{emails.admin}',
        '{emails.manager}',
    ],
];
```

> [!TIP]
> При объединении ссылок в строковом параметре допускается объединение
> числовых и строковых значений параметров
> ```php
>   return [
>       'api.host' => 'api.example.com',
>       'api.host_port' => 8080,
>       'api.url' => '{api.host}:{api.host_port}',
>   ];
> ```
> Значение параметра `'api.url'` будет приведено к строке `'api.example.com:8080'`.

> [!TIP]
> Если значение параметра содержит символ `{` который может быть интерпретирован как ссылка на имя параметра, 
> его необходимо экранировать, добавив еще один `{`, чтобы избежать интерпретации его как ссылки на имя параметра.
> ```php
>  // файл /app/config/escaped_parameters.php
>  return [
>       'parameter.escaped' => 'foo{{bar}baz',
>  ];
> ```
> Значение параметра `'parameter.escaped'` будет приведено к виду
> `'foo{bar}baz'`.

## Конфигурирование параметров метода php-класса или callable выражения.

### Хелпер функция diParameter.
Хелпер функция `diParameter` используется при конфигурировании контейнера [как php определений](01-php-definition.md).
Указать параметр можно по индексу или через именованные аргументы:
```php
// /app/config/services.php 
use App\Services\Foo;
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function () {
    yield diAutowire(Foo::class)
        ->bindArguments(
            // установить в параметр с индексом 0 значение параметра 'params.one'
            diParameter('params.one'),
            // установить в параметр с именем 'baz' значение параметра 'params.two'
            baz: diParameter('params.two'),
        )
    ;
};
```
> [!TIP]
> Хелпер функция `diParameter` предоставляет возможность автоматически определить
> имя параметра контейнера.

Автоматический подбор имени параметра контейнера у аргумента конструктора класса:
```php
// /app/config/services.php
use App\Services\Baz;
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function () {
    yield diAutowire(Baz::class)
        ->bindArguments(
        /**
         * 🚩 Попытаться определить имя параметра
         * исходя из имени параметра под индексом 0
         * Эквивалентно `diParameter('adminEmail')` 
         */
        diParameter(),
        /**
         * 🚩 Подставить имя аргумента из имени параметра 'host'
         * Эквивалентно `diParameter('host')` 
         */ 
        host: diParameter(), 
    );
};
```
```php
namespace App\Services;

final class Baz {
    public function __construct(
        private string $adminEmail,
        private string $host,
    ) {}
}
```
### PHP атрибут Parameter.
Php атрибут `Parameter` предназначен для указания параметру метода php класса или callable выражения какой
«параметр контейнера» необходимо использовать при [конфигурации определений через PHP атрибуты](02-attribute-definition.md).
```php
namespace App\Services;

use App\Services\Qux;
use Kaspi\DiContainer\Attributes\{Inject, Parameter};

final class Foo {
    public function __construct(
        #[Inject(Qux::class)]
        private QuxInterface $qux,
        #[Parameter('adminEmail')]
        private string $adminEmail,
        #[Parameter('params.baz')]
        private bool $baz,
    ) {}
}
```
Конфигурационный файл параметров контейнера:
```php
// /app/config/parameters.php
return [
    'adminEmail' => 'admin@example.com',
    'params.baz' => false,
];
```
Сборка контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->import('App\\', src: '/app/src/')
    ->build()
;
```
> [!TIP]
> Php атрибут `Parameter` предоставляет возможность автоматически определить 
> имя параметра контейнера.

Автоматический подбор имени параметра контейнера у аргумента конструктора класса:
```php
namespace App\Services;

use App\Services\Qux;
use Kaspi\DiContainer\Attributes\{Inject, Parameter};

final class Foo {
    public function __construct(
        #[Inject(Qux::class)]
        private QuxInterface $qux,
        // 🚩 Эквивалентно объявлению `#[Parameter('adminEmail')]` 
        #[Parameter]
        private string $adminEmail
    ) {}
}
```
## Параметры контейнера определяемые во время выполнения.
Некоторые параметры контейнера нельзя определить в конфигурационных файлах,
поскольку значение параметра вычисляется во время выполнения с использованием зависимостей контейнера.

Для [компилируемого контейнера](06-container-builder.md#компиляция-контейнера) важно дать знать о будущем существовании «параметра контейнера».
Для таких случаев есть определение «параметра контейнера времени исполнения».

### Хелпер функция diParameterRuntime.
Хелпер функция `diParameterRuntime` используется при [конфигурировании контейнера как php определений](01-php-definition.md#diparameterruntime).

### PHP атрибут ParameterRuntime.
Php атрибут `ParameterRuntime` необходимо использовать при [конфигурации определений через PHP атрибуты](02-attribute-definition.md#parameterruntime).

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
