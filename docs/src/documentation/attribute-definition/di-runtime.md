---
outline: [2, 3]
---
# DiRuntime

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\DiRuntime` объявляет PHP класс в контейнере, реальное состояние которого будет инициализировано вне контейнера, и его значение будет установлено только во время выполнения.

Такой PHP класс в рамках контейнера зависимостей называется **«[Runtime Definition](../runtime-definition/index.md)»**.

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`
- `\Attribute::IS_REPEATABLE`

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
- `$resetter` – конфигурация для сброса состояния объекта.

> [!TIP]
> Изучите разделы документации:
> - [Сброс состояния объектов](../12-object-resetters.md).


## Идентификатор контейнера { #container-id }

При объявлении PHP класса через атрибут `\Kaspi\DiContainer\Attributes\DiRuntime` пустая строка в параметре `$containerIdentifier` интерпретируется контейнером как идентификатор контейнера представленный FQCN[^FQCN] объявляемого PHP класса.

PHP класс объявленный ниже будет доступен через идентификатор контейнера `'App\Services\FooServices'`:

::: code-group

```php [FooService.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;//[!code ++]

#[DiRuntime]//[!code ++]
final class FooService
{
    // …
}
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\FooService;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$container->has(FooService::class);// [!code highlight]
// (bool) true
```

:::

### Объявление с несколькими идентификаторами { #container-id-multuple }

Атрибут `\Kaspi\DiContainer\Attributes\DiRuntime` может объявлять один и тот же PHP класс в контейнере с разными идентификаторами[^ContainerId].

```php
<?php
declare(strict_types=1);

// file: /app/src/Services/FooService.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;//[!code ++]

#[DiRuntime]//[!code ++]
#[DiRuntime('services.foo_service')]//[!code ++]
final class FooService
{
    // …
}
```

## Теги { #tags }

Параметр `$tags` атрибута `\Kaspi\DiContainer\Attributes\DiRuntime` устанавливает тег и метаданные для объявляемого PHP класса, используя [атрибут `\Kaspi\DiContainer\Attributes\Tag`](tag.md).

Значение `null` в параметре `$tags` указывает контейнеру на необходимость **установить тег через атрибут `Tag` указанный в объявляемом PHP классе**.

Значение `[]` (пустой массив) в параметре `$tags` указывает контейнеру **не устанавливать тег к объявляемому PHP классу**, даже если у класса указан атрибут `Tag`.

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiRuntime;
use Kaspi\DiContainer\Attributes\Tag;

#[DiRuntime(
    tags: [//[!code highlight]
        new Tag('tags.fuze'),//[!code ++]
        new Tag('tags.runtime_service'),//[!code ++]
    ]//[!code highlight]
)]
final class FooService
{
    // …
}
```

<!--@include: ../_include/term_notes.md-->
