---
outline: [2, 4]
---
# Autowire

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` позволяет конфигурировать PHP класс как определение для контейнера и может применяться к PHP классу или [к параметру метода (функции)](#autowire-on-param).

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Autowire::__construct(
    string $id = '',
    ?bool $isSingleton = null,
    array $arguments = [],
    array|\Kaspi\DiContainer\Attributes\Tag|null $tags = null,
    ?array $setups = null,
    callable|false|string $resetter = false,
    bool $isLazy = false,
)
```

Параметры:

- `$id` – идентификатор контейнера для класса (_container identifier_).
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments` – предать аргументы для конструктора PHP класса.
- `$tags` – указание тегов к конкретному идентификатору контейнера указанному в параметре `$id`.
- `$setups` – указание сеттер методов PHP класса для настройки PHP класса к конкретному идентификатору контейнера указанному в параметре `$id`.
- `$resetter` – значение которое будет вызвано [для сброса состояния объекта](../12-object-resetters.md).
- `$isLazy` – обозначение определения как «ленивый объект». Подробнее в разделе – [Внедрение «ленивых» объектов контейнером](../14-lazy-injection.md).

## Аргументы для конструктора PHP класса { #arguments }

<!--@include: ./_include/arguments.md-->

Конфигурирование класса:

::: code-group

```php [FooService.php]
// file: /app/src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\{
    DiDefinitionAutowire as DiAutowire,
    DiDefinitionGet as DiGet,
    DiDefinitionParameter as DiParameter
};
use App\Interfaces\QuxInterface;

#[Autowire(arguments: [
    'qux' => new DiGet(Foo::class),
    'adminEmail' => new DiParameter('adminEmail'),       
])]
#[Autowire(id: 'services.foo_service.with_bar', arguments: [
    'qux' => new DiAutowire(Bar::class),
    'adminEmail' => new DiParameter('adminEmail'),
])]
class FooService
{
    public function __construct(
        public readonly Baz $baz,
        public readonly QuxInterface $qux,
        public readonly string $adminEmail
    ) {}
}
```

```php [QuxInterface.php]
// file: /app/src/Interfaces/QuxInterface.php
namespace App\Interfaces;

interface QuxInterface {}
```

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo implements QuxInterface
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

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

:::

Параметры контейнера:

```php
// file: /app/config/parameters/params.php
return [
    'adminEmail' => 'admin@example.com',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooService, Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters/params.php')
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

var_dump($container->has(FooService::class));
// (bool) true

var_dump($container->has('services.foo_service.with_bar'));
// (bool) true

$fooService = $container->get(FooService::class);
$fooServicesWithBar = $container->get('services.foo_service.with_bar');

var_dump($fooService->qux instanceof Foo);
// (bool) true
var_dump($fooService->baz instanceof Baz);
// (bool) true

var_dump($fooServicesWithBar->qux instanceof Bar);
// (bool) true
var_dump($fooServicesWithBar->baz instanceof Baz);
// (bool) true


```

## Идентификатор контейнера { #container-id }

**Для атрибута примененного к PHP классу** пустая строка в `\Kaspi\DiContainer\Attributes\Autowire::$id` будет интерпретирована контейнером как полное имя класса (_Fully Qualified Class Name_):

```php
// /app/src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(arguments: [])]
/**
 * 🚩 Эквивалентно объявлению
 * #[Autowire(id: FooService::class, arguments: [])] 
 */
final class FooService
{
    // …
}
```

### Несколько атрибутов для одного PHP класса { #container-id-multuple }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` можно применить несколько раз к одному PHP классу.
Параметр `\Kaspi\DiContainer\Attributes\Autowire:$id` должен быть уникальным для каждого атрибута примененного к PHP классу:

```php
// /app/src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire]
#[Autowire(id: 'services.foo_service')]
final class FooService
{
    // …
}
```

Доступность PHP класса по идентификаторам контейнера:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\FooService;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build()
;

var_dump($container->has(FooService::class));
// (bool) true
var_dump($container->has('services.foo_service'));
// (bool) true
```

## Внедрение зависимостей через сеттер-методы { #setups }

## Указание тегов { #tags }


> [!NOTE]
> Значение переданное параметру `\Kaspi\DiContainer\Attributes\Autowire::$tags` определит как будет сконфигурирован PHP класс:
> - значение по умолчанию `null` – конфигурировать через [атрибуты `\Kaspi\DiContainer\Attributes\Tag`](#tag) примененные к текущему классу.
> - массив из атрибутов `\Kaspi\DiContainer\Attributes\Tag` или одиночный атрибут `\Kaspi\DiContainer\Attributes\Tag` – конфигурировать теги из указанных значений.
>   - типизация параметра `list<Tag>|Tag`
> - значение пустой массив (`empty-array` aka `[]`) – не применять никаких тегов к определению.
>


## Применение `Autowire` к параметру метода { #autowire-on-param }