# Parameter

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Parameter` внедряет зависимость из «параметров контейнера».

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Parameter::__construct(
    string $name = ''
)
```
Параметры:
- `$name` – имя параметра контейнера.

> [!TIP]
> Более подробное описание в разделе [«Параметры контейнера»](../09-container-parameters.md).

## Пример внедрения параметра контейнера { #example }

Конфигурирование PHP классов:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Parameter;

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        #[Parameter('emails.manager')]
        public readonly string $email,
    ) {}
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

:::

Параметры контейнера:

```php
// file: /app/config/parameters.php

return [
    'emails.manager' => 'manager@example.com',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->bar instanceof Bar);
// (bool) true

var_dump($foo->email);
// string(19) "manager@example.com"
```
