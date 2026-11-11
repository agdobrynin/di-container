---
outline: [2, 4]
title: «Параметры контейнера» времени выполнения
---
### «Параметры контейнера» времени выполнения { #title }

В некоторых сценариях использования контейнера может возникнуть ситуация когда необходимо объявить «параметр контейнера», но значение этого параметра заранее не известно, т.е. на момент конфигурирования известно только имя параметра контейнера.

Реальное значение такого параметра вычисляется вне контейнера, и устанавливается в контейнер только во время выполнения, по заранее определенному имени параметра контейнера.

Такие параметры контейнера называются – **«параметр контейнера» времени исполнения**.

Во время [компиляции контейнера](../container-builder/methods/compile-to-file.md) объявленные внедрения зависимостей должны знать о существовании **«параметров контейнера» времени исполнения**, значения которых будет установлено в контейнер только во время выполнения самого контейнера.

### Внедрение «параметра контейнера» времени выполнения:

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

### Пример внедрения «параметра контейнера» времени выполнения { #example }

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

#### Объявление в файлах конфигураций { #helper-fn-di-parameter-runtime }

В файле конфигураций `services.php` объявить PHP-класс `\App\Services\Foo`, объявить внедрение для параметра конструктора `$setupType` через [хелпер функцию `diParameterRuntime()`](../php-definition/di-parameter-runtime.md), для параметров конструктора `$mailTo` и `$debug`, значения которых заранее известно, через [хелпер функцию `diParameter()`](../php-definition/di-parameter.md).

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Enums\SetupEnum;


$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params_main.php')//[!code highlight]
    ->loadParameters('/app/config/params_notify.php')//[!code highlight]
    ->load('/app/config/services.php')// [!code highlight]
    ->build();


// …

$mockSetupTypeValue = static fn () => SetupEnum::LOCAL;// [!code highlight]

$calculatedSetupTypeValue = ($mockSetupTypeValue)();// [!code ++]

$container->parameters()// [!code ++]
    ->set(// [!code ++]
        'params.setup_type',// [!code ++]
        $calculatedSetupTypeValue,// [!code ++]
    );// [!code ++]

// …

$foo = $container->get(Foo::class);

var_dump($foo->bar);
var_dump($foo->setupType);
var_dump($foo->mailTo);
var_dump($foo->debug);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php  // [!code highlight]

use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diParameterRuntime, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(//[!code highlight]
            setupType: diParameterRuntime('params.setup_type'),//[!code ++]
            mailTo: diParameter('emails.notify'),//[!code highlight]
            debug: diParameter('app.debug'),//[!code highlight]
        );//[!code highlight]
};
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
object(App\Services\Bar)#2 (0) {
}
enum(App\Enums\SetupEnum::LOCAL)
string(37) "admin@example.com, manager@example.com"
bool(false)
```

#### Объявление через атрибут { #attribute-parameter-runtime }

Объявить внедрение в параметр конструктора `$setupType` через [атрибут `\Kaspi\DiContainer\Attributes\ParameterRuntime`](../attribute-definition/parameter-runtime.md) PHP-класса `\App\Services\Foo`,
для остальных параметров конструктора через [атрибут `\Kaspi\DiContainer\Attributes\Parameter`](../attribute-definition/parameter.md).

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use App\Enums\SetupEnum;
use Kaspi\DiContainer\Attributes\Parameter;// [!code highlight]
use Kaspi\DiContainer\Attributes\ParameterRuntime;// [!code ++]

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        #[ParameterRuntime('params.setup_type')]// [!code ++]
        public readonly SetupEnum $setupType,
        #[Parameter('emails.notify')]// [!code highlight]
        public readonly string $mailTo,
        #[Parameter('app.debug')]// [!code highlight]
        public readonly bool $debug,
    ) {}
}
```
::: 

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Enums\SetupEnum;


$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/params_main.php')//[!code highlight]
    ->loadParameters('/app/config/params_notify.php')//[!code highlight]
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();


// …

$mockSetupTypeValue = static fn () => SetupEnum::LOCAL;// [!code highlight]

$calculatedSetupTypeValue = ($mockSetupTypeValue)();// [!code ++]

$container->parameters()// [!code ++]
    ->set(// [!code ++]
        'params.setup_type',// [!code ++]
        $calculatedSetupTypeValue,// [!code ++]
    );// [!code ++]

// …

$foo = $container->get(Foo::class);

var_dump($foo->bar);
var_dump($foo->setupType);
var_dump($foo->mailTo);
var_dump($foo->debug);
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
object(App\Services\Bar)#2 (0) {
}
enum(App\Enums\SetupEnum::LOCAL)
string(37) "admin@example.com, manager@example.com"
bool(false)
```
