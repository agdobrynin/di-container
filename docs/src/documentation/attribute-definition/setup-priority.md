# SetupPriority

## Обзор { #overview }

Для изменения приоритета внедрения зависимостей через сеттер-методы используется атрибут `\Kaspi\DiContainer\Attributes\SetupPriority` вместе с атрибутами [`Setup`](setup.md) и [`SetupImmutable`](setup-immutable.md).

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\SetupPriority::__construct(
    int $priority = 0
)
```

Параметры:
- `$priority` - приоритет вызова.

Чем выше значение параметра `$priority` тем выше приоритет сеттер-метода.

## Пример изменения приоритета сеттер-методов { #example }

```php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupPriority;

class Foo
{
    // …

    #[Setup]
    public function bar(Bar $bar): void
    {
        // …
    }

    #[Setup]
    #[SetupPriority(10)]
    public function baz(Baz $baz): void
    {
        // …
    }
}
```

Атрибут `\Kaspi\DiContainer\Attributes\SetupPriority` имеет значение `$priority = 10` для сеттер-метода `\App\Services\Foo::baz()`
поэтому этот метод будет вызван первым, вторым будет вызван метод `\App\Services\Foo::bar()`.
