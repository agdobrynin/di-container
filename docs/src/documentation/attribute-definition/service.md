# Service

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Service` указывает PHP интерфейсу его реализацию.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Service::__construct(
    string $id,
    ?bool $isSingleton = null
)
```

Параметры:
- `$id` – FQCN [^FQCN] PHP класса реализующего интерфейс или идентификатор контейнера.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

## Пример конфигурирования интерфейса { #example-interface-service }

Конфигурирование PHP классов:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo
{
    public function __construct(public readonly QuxInterface $qux) {} 
}
```

```php [QuxInterface.php]
// file: /app/src/Interfaces/QuxInterface.php
namespace App\Interfaces;

use Kaspi\DiContainer\Attributes\Service;
use App\Services\Bar;

// класс реализующий данный интерфейс.
#[Service(Bar::class)]
interface QuxInterface
{
    // …
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

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

var_dump($container->get(Foo::class)->qux instanceof Bar);
// (bool) true
```

## Ссылка на идентификатор контейнера { #service-container-id }

Параметр `\Kaspi\DiContainer\Attributes\Service::$id` может быть ссылкой идентификатор контейнера.

```php
// file: /app/src/Interfaces/BazInterface.php
namespace App\Interfaces;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.foo')]
interface BazInterface
{
    // …
} 
```
⚠️⚠️⚠️⚠️ нужно дополнить

<!--@include: ../_include/term_notes.md-->
