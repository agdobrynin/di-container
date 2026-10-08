# diGet

## Обзор { #overview }

Хелпер функция `diGet()` внедряет зависимость через обращение по идентификатору контейнера,
это эквивалентно вызову метода `get()` контейнера зависимостей.

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diGet(
    string $containerIdentifier
): DiDefinitionNoArgumentsInterface
```

Параметры:
- `$containerIdentifier` – содержит указание на идентификатор контейнера, или указание на имя PHP класса который может быть получен контейнером.

У хелпер функции нет дополнительных методов.

## Пример { #example }

Пример с указанием на идентификатор контейнера.

Конфигурирование:

```php
// file: /app/config/services.php

use function Kaspi\DiContainer\{
    diAutowire, diCallable, diGet
};
use App\Service\{Foo, Bar};

return static function (): \Generator {

    yield 'services.bar_custom' => diAutowire(Bar::class)
        ->setup('doSetupOne') // внедрение параметров через сеттер-методы
        ->setup('doSetupTwo') // внедрение параметров через сеттер-методы
        ;

    yield diAutowire(Foo::class)
        ->bindArguments(bar: diGet('services.bar_custom'));
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Service\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->load(...\glob('/app/config/*.php'))
    ->build()
;

$foo = $container->get(Foo::class);

var_dump($foo->bar instanceof Bar);
// bool(true)
```

Классы для конфигурирования:

::: code-group

```php[Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

final class Foo
{
    public function __construct(public readonly Bar $bar) {}
}
```

```php[Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

final class Bar
{
    // …
    
    public function doSetupOne(): void
    {
        // …
    }

    public function doSetupTwo(): void
    {
        // …
    }
}
```

:::