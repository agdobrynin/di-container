# DiFactory

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\DiFactory` внедряет зависимость контейнера через паттерн «фабрика» [^FactoryPattern].

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\DiFactory::__construct(
    string|array $definition,
    ?bool $isSingleton = null,
    array $arguments = []
)
```

Параметры:
- `$definition` – php класс и метод фабрика.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments` – аргументы метода фабрики.

Подробное описание в разделе документации [«Использование фабричных методов для разрешения зависимостей»](../07-factory.md).

## Пример использования фабрики для конфигурирования PHP класса { #example-do-config-php-class }

Использование не статического фабричного метода для внедрения зависимости.

Конфигурирование PHP классов:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use App\Helpers\Configurator;

final class Foo
{
    public function __construct(
        #[DiFactory([Configurator::class, 'barDoConfig'])]
        public readonly Bar $bar
    ) {}    
}
```

```php [Configurator.php]
// file: /app/src/Helpers/Configurator.php
namespace App\Helpers;

use App\Services\Bar;

final class Configurator
{
    public function __construct(public readonly Bar $bar) {}
    
    public function barDoConfig(): Bar
    {
        $this->bar->calculateSomething();
        $this->bar->baz->calculateSomething();

        return $this->bar;
    }   
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    public function __construct(
        public readonly Baz $baz
    ) {}
    
    public function calculateSomething(): void
    {
        // …
        // Дополнительные вычисления в классе 
    }
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
    public function calculateSomething(): void
    {
        // …
        // Дополнительные вычисления в классе 
    }
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->bar instanceof Bar);
// (bool) true

var_dump($foo->bar->baz instanceof Baz);
// (bool) true
```


<!--@include: ../_include/term_notes.md-->
