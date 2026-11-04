---
outline: [2, 4]
---
# Внедрение «параметров контейнера» { #title }

## Обзор { #overview }

Внедрение в параметры метода или вызываемого типа[^CallableType] «параметров контейнера»:
- через [хелпер функцию `diParameter()`](../php-definition/di-parameter.md) в [файлах конфигураций](../container-builder/configuration_files.md).
- через [атрибут `\Kaspi\DiContainer\Attributes\Parameter`](../attribute-definition/parameter.md).

Хелпер функция и атрибут принимают один аргумент в параметре `$name` – имя «параметра контейнера».

> [!TIP]
> Контейнер попытается автоматически определить имя «параметра контейнера» исходя из имени параметра метода (функции) или именованного аргумента если в параметр `$name` передана пустая строка.

## Пример внедрения «параметра контейнера» { #example }

PHP-классы:

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use App\Enums\SetupEnum;

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        public readonly SetupEnum $setupType,
        public readonly string $mailTo,
        public readonly bool $debug,
    ) {}
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

### Объявление в файлах конфигураций { #helper-fn-di-parameter }

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

$container->->build();

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

### Объявление через атрибут { #attribute-parameter }

### 🚧🚧🚧🚧

Конфигурирование через атрибуты:

```php
// file: /app/src/Services/Foo.php
namespace App\Services;

use App\Interfaces\QuxInterface;
use App\Enums\Setup;
use App\Services\Bar;// [!code ++]
use Kaspi\DiContainer\Attributes\Inject;// [!code ++]
use Kaspi\DiContainer\Attributes\Parameter;// [!code ++]

final class Foo
{
    public function __construct(
        #[Inject(Bar::class)]// [!code ++]
        public readonly QuxInterface $qux,
        #[Parameter] // Эквивалентно объявлению #[Parameter('setupType')] // [!code ++]
        public readonly Setup $setupType,
        #[Parameter] // Эквивалентно объявлению #[Parameter('mailTo')] // [!code ++]
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
    ->loadParameters(
        '/app/config/parameters.php',
        '/app/config/setup_parameters.php',
    )
    ->load('/app/config/services.php')// [!code --]
    ->import(namespace: 'App\\', src: '/app/src')// [!code ++]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->qux instanceof Bar);
// bool(true)

var_dump($foo->qux instanceof QuxInterface);
// bool(true)

var_dump($foo->setupType === Setup::LOCAL);
// bool(true)

var_dump($foo->mailTo);
// string(19) "manager@example.com"

var_dump($foo->baz);
// bool(true)
```

<!--@include: ../_include/term_notes.md-->