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

### Объявление в файлах конфигураций { #helper-fn-di-parameter-runtime }

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

### Объявление через атрибут { #attribute-parameter-runtime }

Объявить внедрение в параметр конструктора `$setupType` через [атрибут `\Kaspi\DiContainer\Attributes\ParameterRuntime`](../attribute-definition/parameter-runtime.md) PHP-класса `\App\Services\Foo`,
для остальных параметров конструктора через [атрибут `\Kaspi\DiContainer\Attributes\Parameter`](../attribute-definition/parameter.md).

🚧🚧🚧

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
