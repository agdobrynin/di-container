# InjectByCallable

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\InjectByCallable` внедряет зависимость через вызываемый [тип `callable`](https://www.php.net/manual/en/language.types.callable.php).

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\InjectByCallable::__construct(
    callable $callable
)
```
Параметры:
- `$callable` – вызываемый тип для получения результата внедрения.

Параметры в вызываемом типе могут быть внедрены контейнером автоматически на основании конфигурации контейнера.

## Пример внедрения { #example }

Конфигурация классов:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\InjectByCallable;
use App\Helpers\Configurator;

class Foo
{
    public function __construct(
        // вызов статического метода класса
        #[InjectByCallable([Configurator::class, 'doConfigBar'])]
        public readonly Bar $bar,
        // вызов функции
        #[InjectByCallable('App\calculateStr')]
        public readonly string $calcStr,
    ) {}
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    public function __construct(
        public string $val
    ) {}    
}
```

```php [Configurator.php]
// file: /app/src/Helpers/Configurator.php
namespace App\Helpers;

use Kaspi\DiContainer\Attributes\Parameter;
use App\Services\Bar;

final class Configurator
{
    // …
    
    public static function doConfigBar(
        #[Parameter('params.lorem')]
        string $inputStr
    ): Bar {
        $bar = new Bar($inputStr);
        // …
        
        return $bar;   
    }  
}
```

```php [functions.php]
// file /app/src/functions.php
namespace App;

if (!function_exists('App\calculateStr')) {
    function calculateStr(): string {
        // …
        
        return $calculatedString;
    }
}
```

:::

> [!NOTE]
> В конфигурировании параметра `App\Helpers\Configurator::doConfigBar()` используется [атрибут `\Kaspi\DiContainer\Attributes\Parameter`](parameter.md).

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    // 🚩 Добавить параметр контейнера
    ->setParameter('params.lorem, 'Lorem ipsum')
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->bar->val);
// string(11) "Lorem ipsum"
```
