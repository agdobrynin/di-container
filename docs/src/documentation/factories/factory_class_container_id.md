---
outline: [2,4]
---
# Фактическое имя класса фабрики { #title }

## Обзор { #overview }

Обычно PHP-класс «фабрика»[^FactoryPattern] представляется двумя строковыми значениями – имя PHP-класса и имя метода этого класса.

```php
$factoryDefinition = [App\Services\Foo::class, 'methodName'];
```

Для получения результата вызова метода фабрики `methodName()` контейнер сначала выполнит **получение объекта** по идентификатору контейнера или FQCN[^FQCN] через метод контейнера `get()`, в примере выше это вызова `$container->get('App\Services\Foo')`, и только потом выполнит вызова метода `methodName()`. Из этого следует: строковое значение для «имени PHP-класса» должно обеспечивать получение объекта из контейнера по идентификатору контейнера[^ContainerId] в котором должен быть реализован указанный метод, в примере выше это `'methodName'`.

Указание в качестве имени класса фабрики допустимо любого идентификатора контейнера, если получаемый из контейнера объект реализует фабричный метод.

## Пример указания идентификатора контейнера в качестве имени класс фабрики { #example }

PHP-классы:

::: code-group

```php [FooFactory.php]
<?php
declare(strict_types=1);

// file: /app/src/Factories/FooFactory.php //[!code highlight]

namespace App\Factories;

final class FooFactory
{
    public function __construct(
        // other dependencies can be injected here
    ) {}

    public function createFooBar(string $value): Foo
    {
        $bar = new Bar($barValue);
        
        // some settings for $bar
        
        return new Foo($bar);
    }
    
    public function createFooBaz(string $value): Foo
    {
        $baz = new Baz($barValue);
        
        // some settings for $baz
        
        return new Foo($baz);
    }
}
```

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo
{
    public function __construct(public readonly QuxInterface $qux) {}
}
```

```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php //[!code highlight]

namespace App\Interfaces;

interface QuxInterface
{
    // …
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface
{
    public function __construct(public readonly string $value) {}
    
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Baz implements QuxInterface
{
    public function __construct(public readonly string $value) {}
    
    // …
}
```
:::

## 🚧🚧🚧🚧

```php
namespace App\Factories;

use App\Classes\Foo;
use Psr\Log\LoggerInterface;

class ClassFactory
{
    private LoggerInterface $logger;

    public function __construct(private Bar $bar) {}
    
    public function withLogger(LoggerInterface $logger): self
    {
        $new = clone $this;
        $new->logger = $logger;
        
        return $new;
    }

    public function create(): Foo
    {
        $foo = new Foo($this->bar);
        // дополнительное конфигурирование `$foo`
        if (isset($this->logger)) {
            $this->logger->info('Configured class via factory', $foo);
        }
        
        return $foo;
    }
}
```

```php
// config/services.php
use App\Factories\ClassFactory;
use function Kaspi\DiContainer\{diAutowire, diGet};

return function () {
    
    // доступен через метод контейнера `get('factories.class_factory.config_one')`
    yield 'factories.class_factory.config_one' => diAutowire(ClassFactory::class)
        ->setupImmutable('withLogger', diGet('services.app_logger'));

    // использование идентификатора контейнера
    yield Foo::class => diFactory([
        'factories.class_factory.config_one', 'create'
    ]);

};
```
Объявление фабрики через php атрибут:
```php
use Kaspi\DiContainer\Attributes\DiFactory;

#[DiFactory(['factories.class_factory.config_one', 'create'])]
class Foo{}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(\App\Classes\Foo::class);
```
> [!NOTE]
> Класс `\App\Classes\Foo` будет создан через вызов
> метода контейнера `$object = $container->get('factories.class_factory.config_one')`
> и исполнением метода `$object::create()`.
>
> При разрешении фабрики, контейнер попытается разрешить зависимость
> `ClassFactory::$bar` в конструкторе на основе конфигурации.
>

<!--@include: ../_include/term_notes.md-->
