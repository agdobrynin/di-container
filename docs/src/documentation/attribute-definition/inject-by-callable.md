# InjectByCallable

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\InjectByCallable` внедряет зависимость через вызываемый тип[^CallableType].

Конфигурация атрибута:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\InjectByCallable::__construct(
    callable $callable
)
```
Параметры:
- `$callable` – вызываемый тип.

Параметры в вызываемом типе могут быть внедрены контейнером автоматически на основании конфигурации контейнера.

## Пример внедрения через вызываемый тип { #example }

Статический метод `\App\Helpers\Configurator::doConfigBar()` выполняет необходимые вычисления для инициализации PHP класса `\App\Services\Bar`. Статический метод PHP класса является вызываемым типом[^CallableType] поэтому используем атрибут `\Kaspi\DiContainer\Attributes\InjectByCallable` для внедрения зависимости в параметр конструктора `$bar` класса `\App\Services\Foo`.

Во второй параметр `$calcStr` класса `\App\Services\Foo` необходимо внедрить значение вычисляемое функцией `\App\calculate_str()`, внедрение так же объявим через атрибут `\Kaspi\DiContainer\Attributes\InjectByCallable`.


::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\InjectByCallable;//[!code ++]
use App\Helpers\Configurator;

class Foo
{
    public function __construct(
        #[InjectByCallable([Configurator::class, 'doConfigBar'])]//[!code ++]
        public readonly Bar $bar,
        #[InjectByCallable('App\calculate_str')]//[!code ++]
        public readonly string $calcStr,
    ) {}
}
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->setParameter('params.lorem', 'Lorem ipsum')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->bar->val);
// string(11) "Lorem ipsum"

var_dump($foo->calcStr);
// string(10) "121e767853"
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

final class Bar
{
    public function __construct(public readonly string $val) {}    
}
```

```php [Configurator.php]
<?php
declare(strict_types=1);

// file: /app/src/Helpers/Configurator.php // [!code highlight]

namespace App\Helpers;

use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]
use App\Services\Bar;

final class Configurator
{
    // …
    
    public static function doConfigBar(
        #[Parameter('params.lorem')]//[!code ++]
        string $inputStr
    ): Bar {
        $bar = new Bar($inputStr);

        // …
        
        return $bar;   
    }  
}
```

```php [functions.php]
<?php
declare(strict_types=1);

// file /app/src/functions.php // [!code highlight]

namespace App;

use function {bin2hex, random_bytes};

if (!function_exists('App\calculate_str')) {
    function calculate_str(): string {
        $str = bin2hex(random_bytes(5));
        
        // …
        
        return $str;
    }
}
```

:::

> [!NOTE]
> В параметре `App\Helpers\Configurator::doConfigBar()` используется внедрение через [атрибут `\Kaspi\DiContainer\Attributes\Parameter`](parameter.md).

<!--@include: ../_include/term_notes.md-->
