---
outline: [2, 4]
---
# Внедрение «параметров контейнера» { #title }

## Обзор { #overview }

Внедрение «параметра контейнера»:

- в [файлах конфигураций](../container-builder/configuration_files.md) через [хелпер функцию `diParameter()`](../php-definition/di-parameter.md).
- через [атрибут `\Kaspi\DiContainer\Attributes\Parameter`](../attribute-definition/parameter.md).

Хелпер функция и атрибут принимают один аргумент в параметре `$name` – имя «параметра контейнера».

> [!TIP]
> Если в параметр `$name` передается пустая строка, то контейнер попытается автоматически определить имя «параметра контейнера» исходя из имени параметра метода (функции) или именованного аргумента.

## Пример внедрения «параметра контейнера» { #example }

PHP классы:

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

::: code-group

```php [parameters.php]
// file: /app/config/parameters.php

return static function () {
    yield 'mailTo' => 'manager@example.com';
    
    yield 'params.foo' => true;
};
```

```php [setup_parameters.php]
// file: /app/config/setup_parameters.php
use App\Enums\Setup;

return [
    'setupType' => Setup::LOCAL,
];
```

:::

### Хелпер функция `diParameter()` { #helper-fn-di-parameter }

Конфигурирование:

```php
// file: /app/config/services.php
use App\Services\{Foo, Bar};
use function Kaspi\DiContainer\{diAutowire, diGet, diParameter};

return static function () {
    yield diAutowire(Foo::class)
        ->bindArguments(
            diGet(Bar::class),
            /*
             * Параметр # 2 конструктора с именем `$setupType`.
             * Эквивалентно объявлению diParameter('setupType')
             */
            diParameter(),
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
    ->loadParameters(
        '/app/config/parameters.php',
        '/app/config/setup_parameters.php',
    )
    ->load('/app/config/services.php')
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

### Атрибут `Parameter` { #attribute-parameter }

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
