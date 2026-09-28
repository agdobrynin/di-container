---
outline: [2, 4]
title: Внедрение «параметров контейнера»
---
### Внедрение «параметров контейнера» { #title }

Внедрение в параметры метода или вызываемого типа[^CallableType] «параметров контейнера»:
- через [хелпер функцию `diParameter()`](../php-definition/di-parameter.md) в [файлах конфигураций](../container-builder/configuration_files.md).
- через [атрибут `\Kaspi\DiContainer\Attributes\Parameter`](../attribute-definition/parameter.md).

Хелпер функция и атрибут принимают один аргумент в параметре `$name` – имя «параметра контейнера».

> [!TIP]
> <!--@include: _include/param_name_on_empty_arg.md-->

### Пример внедрения «параметра контейнера» { #example }

PHP-классы:

::: code-group
<<< @/_code/src/Services/Foo.php
<<< @/_code/src/Services/Bar.php
<<< @/_code/src/Enums/SetupEnum.php
:::

Файлы конфигурации параметров контейнера:

::: code-group
<<< @/_code/config/params.php
<<< @/_code/config/params_dev.php
<<< @/_code/config/params_notify.php
:::

> [!IMPORTANT]
> <!--@include: _include/declare_order.md-->

Внедрить через параметры контейнера значения в конструктор PHP-класса `\App\Services\Foo`.

Параметры контейнера должны быть определены с учетом следующего условия: если переменная окружения `APP_ENV` установлена в значение `'dev'`, параметры контейнера должны загружаться из файла `params_dev.php`; во всех остальных случаях они должны загружаться из файла `params.php`.

#### Объявление в файлах конфигураций { #helper-fn-di-parameter }

В файле конфигураций `services.php` объявить PHP-класс `\App\Services\Foo`, и объявить внедрение параметров контейнера в конструктор этого класса через хелпер функцию `diParameter()`:
- `'setupType'` в параметр конструктора `$setupType`.
- `'emails.notify'` в параметр конструктора `$mailTo`.
- `'app.debug'` в параметр конструктора `$debug`.
 

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

use function {getenv, putenv};

putenv('APP_ENV=dev');//[!code ++]

$builder = (new DiContainerBuilder())
    ->loadParameters('/app/config/params.php')//[!code ++]
    ->loadParameters('/app/config/params_notify.php')//[!code ++]
    ->load('/app/config/services.php');// [!code highlight]

if ('dev' === getenv('APP_ENV')) {//[!code ++]
    $builder->loadParameters('/app/config/params_dev.php');//[!code ++]
}//[!code ++]

$container = $builder->build();

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

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(//[!code ++]
            /*
             * Параметр # 2 конструктора с именем `$setupType`.
             * Эквивалентно объявлению diParameter('setupType')
             */
            setupType: diParameter(),//[!code ++]
            mailTo: diParameter('emails.notify'),//[!code ++]
            debug: diParameter('app.debug'),//[!code ++]
        );//[!code ++]
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
string(33) "admin@localhost, manager@localhost"
bool(true)
```

#### Объявление через атрибут { #attribute-parameter }

Объявить внедрение параметров контейнера через атрибут `\Kaspi\DiContainer\Attributes\Parameter` в конструктор PHP-класса `\App\Services\Foo`.

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Enums\SetupEnum;
use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        // Эквивалентно объявлению #[Parameter('setupType')]//[!code warning]
        #[Parameter]//[!code ++]
        public readonly SetupEnum $setupType,
        #[Parameter('emails.notify')]//[!code ++]
        public readonly string $mailTo,
        #[Parameter('app.debug')]//[!code ++]
        public readonly bool $debug,
    ) {}
}
```
::: 

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

use function {getenv, putenv};//[!code ++]

putenv('APP_ENV=dev');

$builder = (new DiContainerBuilder())
    ->loadParameters('/app/config/params.php')//[!code ++]
    ->loadParameters('/app/config/params_notify.php');//[!code ++]

if ('dev' === getenv('APP_ENV')) {//[!code ++]
    $builder->loadParameters('/app/config/params_dev.php');//[!code ++]
}//[!code ++]

$container = $builder->build();

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
string(33) "admin@localhost, manager@localhost"
bool(true)
```

<!--@include: ../_include/term_notes.md-->