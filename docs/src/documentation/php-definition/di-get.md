# diGet()

## Обзор { #overview }

Хелпер функция `diGet()` внедряет зависимость через обращение по идентификатору контейнера [^ContainerId],
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

::: code-group

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php// [!code highlight]

use App\Service\Foo;

use function Kaspi\DiContainer\{diAutowire, diGet};


return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            bar: diGet('services.bar_custom')// [!code ++]
        );
};
```

```php [custom_services.php]
<?php
declare(strict_types=1);

// file: /app/config/custom_services.php// [!code highlight]

use App\Service\Bar;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {

    yield 'services.bar_custom' => diAutowire(Bar::class)
        ->setup('doSetupOne')
        ->setup('doSetupTwo');
    
    // …
};
```

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php// [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/custom_services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->bar->getValues());
// string(7) "One Two"
```

```php [Foo.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(public readonly Bar $bar) {}
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Bar.php// [!code highlight]

namespace App\Services;

use function implode;

final class Bar
{
    /**
     * @var list<non-empty-string> 
     */
    private array $values = [];
    
    public function doSetupOne(): void
    {
        $this->values[] = 'One';
    }

    public function doSetupTwo(): void
    {
        $this->values[] = 'Two';
    }
    
    public function getValues(): string
    {
        return implode(' ', $this->values);
    }
}
```

<!--@include: ../_include/term_notes.md-->
