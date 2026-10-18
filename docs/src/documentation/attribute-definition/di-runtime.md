# DiRuntime

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\DiRuntime` конфигурирует PHP класс, реальное состояние которого будет инициализировано вне контейнера, и установлено в контейнер зависимостей только во время выполнения.

Такой PHP класс в рамках контейнера зависимостей называется **«[Runtime Definition](../runtime-definition/index.md)»**.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\DiRuntime::__construct(
    string $containerIdentifier = '',
    ?string $message = null,
    array|\Kaspi\DiContainer\Attributes\Tag|null $tags = null,
    callable|false|string $resetter = false,
)
```
Параметры:
- `$containerIdentifierOrClass` – идентификатора контейнера[^ContainerId] для «runtime definition».
- `$message` – сообщение при возникновении ошибки.
- `$tags` – тег с метаданными.
- `$resetter` – конфигурация для [сброса состояния объекта](../12-object-resetters.md).

## Идентификатор контейнера { #container-id }

**Для атрибута примененного к PHP классу** пустая строка в параметре `\Kaspi\DiContainer\Attributes\DiRuntime::$containerIdentifier` интерпретируется контейнером как имя класса FQCN[^FQCN]

```php
// file: /app/src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
/**
 * 🚩 Эквивалентно объявлению
 * #[DiRuntime(\App\Services\FooService::class)] 
 */
final class FooService
{
    // …
}
```

### Множественная конфигурация PHP класса { #container-id-multuple }

Атрибут `\Kaspi\DiContainer\Attributes\DiRuntime` можно применить несколько раз к одному PHP классу.
Параметр `\Kaspi\DiContainer\Attributes\DiRuntime:$containerIdentifier` должен быть уникальным для каждого атрибута примененного к PHP классу:

```php
// /app/src/Services/FooService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
#[DiRuntime('services.foo_service')]
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

$fooService = new FooService();

$container->set(FooService::class, $fooService);
$container->set('services.foo_service', $fooService);

var_dump($container->has(FooService::class));
// (bool) true

var_dump($container->has('services.foo_service'));
// (bool) true
```

## Теги { #tags }

Теги с метаданными устанавливаются через параметр `\Kaspi\DiContainer\Attributes\DiRuntime::$tags`.
Теги будут привязаны к определению контейнера с указанным [идентификатором](#container-id-multuple)

Типизация параметра `\Kaspi\DiContainer\Attributes\DiRuntime::$tags`:
- `non-empty-list<\Kaspi\DiContainer\Attributes\Tag>`
- `\Kaspi\DiContainer\Attributes\Tag`
- `empty-list`
- `null`

Значение переданное параметру `\Kaspi\DiContainer\Attributes\DiRuntime::$tags` определит как будет сконфигурированы теги:
- `array` – не пустой массив. Конфигурировать теги из элементов массива с типом `\Kaspi\DiContainer\Attributes\Tag`.
- `\Kaspi\DiContainer\Attributes\Tag` - конфигурировать один тег.
- `array` пустой массив (`empty-array` aka `[]`). Не конфигурировать никакие теги.
- `null` – значение по умолчанию. Конфигурировать теги через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](tag.md) примененные к PHP классу.

<!--@include: ../_include/term_notes.md-->
