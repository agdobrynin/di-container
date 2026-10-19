# AutowireExclude

## Обзор { #overview }


Атрибут `\Kaspi\DiContainer\Attributes\AutowireExclude` исключает из внедряемых зависимостей контейнера PHP класс или интерфейс. FQCN [^FQCN] будет недоступен для метода контейнера `get()` и `has()`.  

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\AutowireExclude::__construct()
```

У атрибута нет параметров.

> [!WARNING]
> Атрибут `\Kaspi\DiContainer\Attributes\AutowireExclude` исключает из конфигурирования любые другие атрибуты.

## Пример исключения PHP класса { #example }

Объявление исключающее PHP класс:

```php
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\AutowireExclude;

#[Autowire(isSingleton: true)] // 🚩 Атрибут будет проигнорирован
#[AutowireExclude]
class Foo {}
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

var_dump($container->has(Foo::class));
// (bool) false
```

<!--@include: ../_include/term_notes.md-->

