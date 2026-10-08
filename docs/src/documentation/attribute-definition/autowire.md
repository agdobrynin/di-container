---
outline: [2, 4]
---
# Autowire

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` конфигурирует PHP класс как определение для контейнера и может применяться к PHP классу или [к параметру метода (функции)](#autowire-on-param).

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
- `$arguments`<span id="constrcut-arguments"/> – аргументы для конструктора PHP класса.
- `$tags` – тег с мета-данными.
- `$setups` – внедрение зависимостей через сеттер-метод [^SetterMethodMutable] [^SetterMethodImmutable].
- `$resetter` – конфигурация [для сброса состояния объекта](../12-object-resetters.md).
- `$isLazy` – обозначение определения как «ленивый объект». Подробнее в разделе – [Внедрение «ленивых» объектов контейнером](../14-lazy-injection.md).

## Идентификатор контейнера { #container-id }

**Для атрибута примененного к PHP классу** пустая строка в параметре `\Kaspi\DiContainer\Attributes\Autowire::$id` интерпретируется контейнером как имя класса FQCN[^FQCN]

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

### Множественная конфигурация PHP класса { #container-id-multuple }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` можно применить несколько раз к одному PHP классу.
Параметр `\Kaspi\DiContainer\Attributes\Autowire:$id` должен быть уникальным для каждого атрибута примененного к PHP классу:

```php
// /app/src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(arguments: [])]
#[Autowire(id: 'services.foo_service', arguments: [])]
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

## Аргументы конструктора PHP класса { #arguments }

[Параметр `\Kaspi\DiContainer\Attributes\Autowire:$arguments`](#constrcut-arguments) передает аргументы конструктору PHP класса.

Для параметров конструктора PHP класса не переданных через аргументы контейнер внедрит зависимости самостоятельно, на основе конфигурации, включая [использование PHP атрибутов](index.md#attributes).

Для передачи неполного списка аргументов `$arguments` указывайте в качестве ключа массива имя параметра.

<!--@include: ./_include/to_arguments.md-->

### Передача аргументов конструктору { #example }

Конфигурирование PHP класса:

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

## Внедрение зависимостей через сеттер-методы { #setups }

Параметр атрибута `\Kaspi\DiContainer\Attributes\Autowire::$setups` внедряет зависимости через сеттер-методы PHP класса.

Атрибут `\Kaspi\DiContainer\Attributes\Setup` следует применять к **мутабельным сеттер-методам** [^SetterMethodMutable].

Атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable` следует применять к **иммутабельным сеттер-методам** [^SetterMethodImmutable].

Типизация параметра `\Kaspi\DiContainer\Attributes\Autowire::$setups`:
- `non-empty-array<none-empty-string, \Kaspi\DiContainer\Attributes\Setup | \Kaspi\DiContainer\Attributes\SetupImmutable>`
- `non-empty-array<none-empty-string, list<\Kaspi\DiContainer\Attributes\Setup | \Kaspi\DiContainer\Attributes\SetupImmutable>>`
- `empty-array`
- `null`

Параметр `\Kaspi\DiContainer\Attributes\Autowire::$setups` определит как и какие сеттер-методы будут вызваны при конфигурировании PHP класса:
 - `array` (aka `non-empty-array`). Ключ массива – имя сеттер-метода, значение элемента атрибут [`Setup`](setup.md), [`SetupImmutable`](setup-immutable.md) или список из этих атрибутов.
- `array` пустой массив (`empty-array` aka `[]`). Не применять никаких сеттер-методов, даже если у методов класса указаны атрибуты `Setup`, `SetupImmutable`.
- `null` значение по умолчанию.  Внедрять зависимости через [атрибут `Setup`](setup.md) и/или [атрибут `SetupImmutable`](setup-immutable.md) указанные у методов класса.


## Теги { #tags }

Теги с мета-данными устанавливаются через параметр `\Kaspi\DiContainer\Attributes\Autowire::$tags`.
Теги будут привязаны к определению контейнера с указанным [идентификатором](#container-id-multuple)

Типизация параметра `\Kaspi\DiContainer\Attributes\Autowire::$tags`:
- `non-empty-list<\Kaspi\DiContainer\Attributes\Tag>`
- `\Kaspi\DiContainer\Attributes\Tag`
- `empty-list`
- `null`

Значение переданное параметру `\Kaspi\DiContainer\Attributes\Autowire::$tags` определит как будет сконфигурированы теги:
 - `array` – не пустой массив. Конфигурировать теги из элементов массива с типом `\Kaspi\DiContainer\Attributes\Tag`.
- `\Kaspi\DiContainer\Attributes\Tag` - конфигурировать один тег.
- `array` пустой массив (`empty-array` aka `[]`). Не конфигурировать никакие теги.
- `null` – значение по умолчанию. Конфигурировать теги через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](tag.md) примененные к PHP классу.

## Внедрения зависимости в параметр метода { #autowire-on-param }

При конфигурировании внедрения зависимости через атрибут `Autowire` в параметр метода (функции),
значение в `\Kaspi\DiContainer\Attributes\Autowire::$id` может быть указано как полное имя класса FQCN [^FQCN] или представлено как пустая строка.

Если в `\Kaspi\DiContainer\Attributes\Autowire::$id` будет пустая строка, то конфигуратор контейнера попытается сформировать значение на основе Type hints [^TypeHints] параметра.

::: code-group

```php [BarService.php]
// file: /app/src/Services/BarService.php
namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

class BarService
{
    public function __construct(
        // 🚩 Указание FQCN 
        #[Autowire(id: QuxService::class)]
        public readonly QuxInterface $qux,

        // Автоматическое формирование $id из типа параметра
        #[Autowire(arguments: [
            new DiParameter('emails.for_service_bar')
        ])]
        // ℹ️ эквивалентно объявлению
        // #[Autowire(
        //      id: Baz::class,
        //      arguments: [
        //          new DiParameter('emails.for_service_bar')
        //      ]
        // )]
        public readonly Baz $baz,
    ) {}
}
```

```php [QuxService.php]
// file: /app/src/Services/QuxService.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class QuxService implements QuxInterface 
{
    // …   
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    public function __construct(
        private string $notifyEmail,
    ) {}
    
    // …
}
```

:::

<!--@include: ../_include/term_notes.md-->
