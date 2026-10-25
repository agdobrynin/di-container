# DiFactory

## Обзор { #overview }

Объявление через атрибут `\Kaspi\DiContainer\Attributes\DiFactory` реализуется через паттерн «фабрика» [^FactoryPattern]. Атрибут объявляет PHP класс в контейнере, или внедряет зависимость.

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\DiFactory::__construct(
    string|array $definition,
    ?bool $isSingleton = null,
    array $arguments = []
)
```

Параметры:
- `$definition` – FQCN[^FQCN] и имя метода.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments` – аргументы метода фабрики.

> [!TIP]
> Изучите раздел документации [«Использование фабричных методов для внедрения зависимостей»](../07-factory.md).

## Пример использования фабрики { #example-di-factory }

Необходимо внедрить зависимость через конструктор в параметр `$bar` PHP класса `\App\Services\Foo` используя фабрику `\App\Helpers\Configurator::barDoConfig()`.


::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;
use App\Helpers\Configurator;

final class Foo
{
    public function __construct(
        #[DiFactory([Configurator::class, 'barDoConfig'])]//[!code ++]
        public readonly Bar $bar
    ) {}    
}
```
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->bar->baz instanceof Baz);
// (bool) true
```

```php [Configurator.php]
<?php
declare(strict_types=1);

// file: /app/src/Helpers/Configurator.php // [!code highlight]

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
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

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
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

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


<!--@include: ../_include/term_notes.md-->
