---
outline: [2, 4]
---
# «Параметры контейнера» времени выполнения { #title }

## Обзор { #overview }

Некоторые «параметры контейнера» нельзя определить в конфигурационных файлах, поскольку значение параметра вычисляется во время выполнения контейнера с использованием зависимостей контейнера.

Для [компилируемого контейнера](../container-builder/index.md#compile-to-file) важно дать знать о будущем существовании «параметра контейнера». Для таких случаев есть определение – **«параметр контейнера» времени исполнения**.

## Внедрение «параметра контейнера» времени выполнения:

- в [файлах конфигураций](../container-builder/configuration_files.md) через [хелпер функцию `diParameterRuntime()`](../php-definition/di-parameter-runtime.md).
- через [атрибут `\Kaspi\DiContainer\Attributes\ParameterRuntime`](../attribute-definition/parameter-runtime.md).

Хелпер функция и атрибут принимают один аргумент в параметре `$name` – имя «параметра контейнера».

Если в `$name` передается пустая строка, то контейнер попытается автоматически определить имя «параметра контейнера» исходя из имени параметра метода (функции) или имени именованного аргумента.

## Пример внедрения «параметра контейнера» времени выполнения { #example }

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use App\Interfaces\QuxInterface;
use App\Enums\Setup;

final class Foo
{
    public function __construct(
        public readonly QuxInterface $qux,
        public readonly Setup $setupType,
        public readonly string $mailTo,
        public readonly bool $baz,
    ) {}
}
```

```php [QuxInterface.php]
// file: /app/src/Interfaces/QuxInterface.php
namespace App\Interfaces;

interface QuxInterface
{
    // …    
}
```

```php [Setup.php]
// file: /app/src/Enums/Setup.php
namespace App\Enums;

enum Setup
{
    case LOCAL;
    case STAGES;
    case PROD; 
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface
{
    // …
}
```

:::


Конфигурация «параметров контейнера»:

```php
// file: /app/config/parameters.php

return static function () {
    yield 'mailTo' => 'manager@example.com';
    
    yield 'params.foo' => true;
};
```

> [!IMPORTANT]
> Значение требуемое для внедрения в параметр `\App\Services\Foo::$setupType` будет известно только после сборки контейнера.


### Хелпер функция `diParameterRuntime()` { #helper-fn-di-parameter-runtime }

Конфигурирование:

```php
// file: /app/config/services.php
use App\Services\{Foo, Bar};
use function Kaspi\DiContainer\{diAutowire, diGet, diParameter, diParameterRuntime};


return static function () {
    yield diAutowire(Foo::class)
        ->bindArguments(
            diGet(Bar::class),
            /*
             * Параметр # 2 конструктора с именем `$setupType`.
             * Эквивалентно объявлению:
             * diParameterRuntime(
             *  'setupType',
             *  'Вы забыли установить параметр контейнера \'setupType\'?'
             * )
             */
            diParameterRuntime(message: 'Вы забыли установить параметр контейнера \'setupType\'?'),
            /*
             * Эквивалентно объявлению
             * mailTo: diParameter('mailTo'),
             */
            mailTo: diParameter(),
            baz: diParameter('params.foo'),
        )
    ;
};
```

Контейнер зависимостей:

```php
use App\Enums\Setup;
use App\Services\{Foo, Bar};
use App\Interfaces\QuxInterface
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')
    ->build();

// 🚩 объявить "параметр контейнера" с именем 'setupType'
$container->parameters()
    ->set('setupType', Setup::STAGES);

$foo = $container->get(Foo::class);

var_dump($foo->qux instanceof Bar);
// bool(true)

var_dump($foo->qux instanceof QuxInterface);
// bool(true)

var_dump($foo->setupType === Setup::LOCAL);
// bool(false)
var_dump($foo->setupType === Setup::STAGES);
// bool(true)

var_dump($foo->mailTo);
// string(19) "manager@example.com"

var_dump($foo->baz);
// bool(true)
```

--------

🚧🚧🚧🚧🚧

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
