---
outline: [2, 4]
---
# «Параметры контейнера» времени выполнения { #title }

## Обзор { #overview }

В некоторых сценариях использования контейнера может возникнуть ситуация когда необходимо объявить «параметр контейнера», но значение этого параметра заранее не известно, т.е. на момент конфигурирования известно только имя параметра контейнера.

Реальное значение такого параметра вычисляется вне контейнера, и устанавливается в контейнер только во время выполнения, по заранее определенному имени параметра контейнера.

Такие параметры контейнера называются – **«параметр контейнера» времени исполнения**.

Для [компилируемого контейнера](../container-builder/methods/compile-to-file.md) важно дать знать о будущем существовании «параметра контейнера». 

## Внедрение «параметра контейнера» времени выполнения:

- через [хелпер функцию `diParameterRuntime()`](../php-definition/di-parameter-runtime.md) в [файлах конфигураций](../container-builder/configuration_files.md).
- через [атрибут `\Kaspi\DiContainer\Attributes\ParameterRuntime`](../attribute-definition/parameter-runtime.md).

Хелпер функция и атрибут принимают один аргумент в параметре `$name` – имя «параметра контейнера».

> [!TIP]
> <!--@include: _include/param_name_on_empty_arg.md-->

Установить реальное значение **«параметра контейнера» времени исполнения** необходимо через метод `set()` объекта реализующего интерфейс `\Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface`, а получить такой объект можно через метод контейнера `parameters()`.

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->build();

// …

// вычисляем значение параметра
$calculatedValue = doCalculateValue();//[!code ++]
// получаем параметры контейнера
$container->parameters()//[!code ++]
    ->set(//[!code ++]
        'params.runtime_foo',//[!code ++]
        $calculatedValue//[!code ++]
    );//[!code ++]
// …
```

## Пример внедрения «параметра контейнера» времени выполнения { #example }

PHP-классы:

::: code-group
<<< @/_code/src/Services/Foo.php
<<< @/_code/src/Services/Bar.php
<<< @/_code/src/Enums/SetupEnum.php
:::

Файлы конфигурации параметров контейнера:


::: code-group
<<< @/_code/config/params_main.php
<<< @/_code/config/params_notify.php
:::

В параметры конструктора `$mailTo` и `$debug` PHP-класса `\App\Services\Foo` объявить внедрение через [параметры контейнера](inject_params.md), для параметра `$setupType` реальное значение вычисляется только вне контейнера, поэтому для этого параметра необходимо использовать объявление внедрения через **«параметр контейнера» времени исполнения**.

## 🚧🚧🚧 

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
             *  'Забыли установить параметр контейнера \'setupType\'?'
             * )
             */
            diParameterRuntime(message: 'Забыли установить параметр контейнера \'setupType\'?'),
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

// 🚩 объявить "параметр контейнера" с именем 'setupType'// [!code highlight]
$container->parameters()// [!code highlight]
    ->set('setupType', Setup::STAGES);// [!code highlight]

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

### Атрибут `ParameterRuntime` { #attribute-parameter-runtime }

Конфигурирование через атрибуты:

```php
// file: /app/src/Services/Foo.php
namespace App\Services;

use App\Interfaces\QuxInterface;
use App\Enums\Setup;
use App\Services\Bar;// [!code ++]
use Kaspi\DiContainer\Attributes\Inject;// [!code ++]
use Kaspi\DiContainer\Attributes\Parameter;// [!code ++]
use Kaspi\DiContainer\Attributes\ParameterRuntime;// [!code ++]

final class Foo
{
    public function __construct(
        #[Inject(Bar::class)]// [!code ++]
        public readonly QuxInterface $qux,
        #[ParameterRuntime(// [!code ++]
            message: 'Забыли установить параметр контейнера \'setupType\'?'// [!code ++]
        )]// [!code ++]
        // Эквивалентно объявлению #[ParameterRuntime('setupType', '...')]// [!code ++]
        public readonly Setup $setupType,
        #[Parameter] // Эквивалентно объявлению Parameter('mailTo') // [!code ++]
        public readonly string $mailTo,
        #[Parameter('params.foo')]// [!code ++]
        public readonly bool $baz,
    ) {}
}
```

Контейнер зависимостей:

```php
use App\Enums\Setup;
use App\Services\{Foo, Bar};
use App\Interfaces\QuxInterface
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')// [!code --]
    ->import(namespace: 'App\\', src: '/app/src')// [!code ++]
    ->build();

// 🚩 объявить "параметр контейнера" с именем 'setupType'// [!code highlight]
$container->parameters()// [!code highlight]
    ->set('setupType', Setup::STAGES);// [!code highlight]

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
