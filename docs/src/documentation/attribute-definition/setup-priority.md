---
outline: [2,4]
title: Атрибут SetupPriority
---
### Атрибут SetupPriority

Атрибут `\Kaspi\DiContainer\Attributes\SetupPriority` изменяет очерёдность внедрения зависимостей через сеттер-методы PHP-класса и используется вместе с атрибутами [`Setup`](setup.md) и [`SetupImmutable`](setup-immutable.md).

Конфигурация атрибута:
- `\Attribute::TARGET_METHOD`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\SetupPriority::__construct(
    int $priority = 0
)
```

Параметры:
- `$priority` - приоритет вызова.

Чем выше значение параметра `$priority`, тем раньше будет внедрена зависимость через сеттер-метод.

### Пример изменения приоритета сеттер-методов { #example }

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use function array_values;

final class Foo
{
    private array $injectedClass = [];

    // …

    public function bar(Bar $bar): void
    {
        $this->injectedClass[Bar::class] = $bar;
    }

    public function baz(Baz $baz): void
    {
        $this->injectedClass[Baz::class] = $baz;
    }
    
    public function getInjectedClass(): array
    {
        return array_values($this->injectedClass);
    }
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

final class Baz
{
    // …
}
```

:::


Необходимо объявить внедрение зависимости через сеттер-методы класса `\App\Services\Foo::bar()` и `\App\Services\Foo::baz()`.

Первым необходимо внедрить зависимость через сеттер-метод `\App\Services\Foo::baz()`, после этого внедрить зависимость через `\App\Services\Foo::bar()`.

Используем атрибут `\Kaspi\DiContainer\Attributes\SetupPriority` для метода `\App\Services\Foo::baz()` чтобы указать очерёдность внедрения через сеттер-методы.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;// [!code ++]
use Kaspi\DiContainer\Attributes\SetupPriority;// [!code ++]

use function array_values;

final class Foo
{
    private array $injectedClass = [];

    // …

    #[Setup]// [!code ++]
    public function bar(Bar $bar): void
    {
        $this->injectedClass[Bar::class] = $bar;
    }

    #[Setup]// [!code ++]
    #[SetupPriority(10)]// [!code ++]
    public function baz(Baz $baz): void
    {
        $this->injectedClass[Baz::class] = $baz;
    }
    
    public function getInjectedClass(): array
    {
        return array_values($this->injectedClass);
    }
}
```

::: 

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->getInjectedClass() as $injectedClass) {
    var_dump($injectedClass::class);
}
```

:::


Выполните в терминале команду:

```shell
php /app/src/container.php
```

Появится сообщение вида:

```console
string(16) "App\Services\Baz"
string(16) "App\Services\Bar"

```

<!--@include: ../_include/term_notes.md-->