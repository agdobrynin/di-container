# ParameterRuntime

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\ParameterRuntime` внедрят зависимость из параметра контейнера времени исполнения[^ParameterRuntime].

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\ParameterRuntime::__construct(
    string $name = '',
    ?string $message = null
)
```

Параметры:
- `$name` – имя параметра контейнера.
- `$message` – дополнительное сообщение, если параметр контейнера еще не определен.

> [!TIP]
> Более подробное описание в разделе [«Параметры контейнера»](../09-container-parameters.md#parameter-runtime).

## Пример внедрения параметра контейнера времени исполнения { #example }

Конфигурирование PHP классов:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\ParameterRuntime;

final class Foo
{
    public function __construct(
        public readonly Bar $bar,
        #[ParameterRuntime('emails.manager')]
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

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->import(namespace: 'App\\', src: '/app/src')
    // 🚩 компиляция контейнера
    ->compileToFile('/app/var/cache/', 'App\AppContainer')
    ->build();

// Функция возвращает строковое значение 'robot213@example.com' 
$email = \App\Helpers\calculateAdminEmail();
// Установка параметра контейнера времени исполнения
$container
    ->parameters()
    ->set('emails.manager', $email);

$foo = $container->get(Foo::class);

var_dump($foo->bar instanceof Bar);
// (bool) true

var_dump($foo->email);
// string(20) "robot213@example.com"
```

<!--@include: ../_include/term_notes.md-->
