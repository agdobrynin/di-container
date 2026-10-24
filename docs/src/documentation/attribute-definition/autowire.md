---
outline: [2, 4]
---
# Autowire

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` объявляет PHP класс в контейнере, или [внедряет зависимость](#autowire-on-param).

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

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

- `$id` – идентификатор контейнера [^ContainerId], или пустая строка.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments`<span id="constrcut-arguments"/> – аргументы для конструктора PHP класса.
- `$tags` – тег с метаданными.
- `$setups` – внедрение зависимостей через сеттер-метод [^SetterMethodMutable] [^SetterMethodImmutable].
- `$resetter` – конфигурация [для сброса состояния объекта](../12-object-resetters.md).
- `$isLazy` – обозначение определения как «ленивый объект». Подробнее в разделе – [Внедрение «ленивых» объектов контейнером](../14-lazy-injection.md).

## Идентификатор контейнера { #container-id }

Значение пустая строка в параметре `$id` интерпретируется контейнером в идентификатор контейнера представленный FQCN[^FQCN] объявляемого PHP класса.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->value);
// string(11) "Lorem ipsum"
```
<<< @/_code/attribute-definition/autowire/Foo.php

:::

### Объявление PHP класса с несколькими идентификаторами { #container-id-multuple }

Атрибут может объявлять один и тот же PHP класс в контейнере с разными идентификаторами[^ContainerId].

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Bar;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

var_dump($container->has(Bar::class));//[!code highlight]
// bool(true)

$bar = $container->get(Bar::class);

var_dump($bar->value);
// int(10)

var_dump($container->has('services.bar_20'));//[!code highlight]
// bool(true)

$bar20 = $container->get('services.bar_20');

var_dump($bar20->value);
// int(20)
```
<<< @/_code/attribute-definition/autowire/Bar.php

:::

## Аргументы конструктора PHP класса { #arguments }

Параметр `$arguments` атрибута `\Kaspi\DiContainer\Attributes\Autowire` передает аргументы конструктору PHP класса.

<!--@include: _include/arguments.md-->

<!--@include: _include/to_arguments.md-->

### Пример объявления аргументов конструктору { #example }

Необходимо объявить PHP класс `\App\Services\FooService` с разными аргументами. 

Для идентификатора `'App\Services\FooService'` в параметр `$qux` необходимо внедрять класс `\App\Services\BarService`, в параметр `$adminEmail` – `'manager@example.com'`. 

Так как идентификатор контейнера должен быть уникальным в рамках контейнера, объявить PHP класс `\App\Services\FooService` через идентификатор контейнера `'services.foo_service.with_baz'`, в параметр `$qux` необходимо внедрять класс `\App\Services\BazService`, в параметр `$adminEmail` – `'admin@example.com'`.

::: code-group

```php
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{FooService, BarService, BazService, BatService};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->addParameters([
        'emails.bar' => 'manager@example.com',//[!code highlight]
        'emails.baz' => 'admin@example.com',//[!code highlight]
    ])
    ->build();

$fooService = $container->get(FooService::class);//[!code warning]

var_dump($fooService->bat instanceof BatService);
// bool(true)

var_dump($fooService->qux instanceof BarService);
// bool(true)

var_dump($fooService->adminEmail);
// string(19) "manager@example.com"

$fooServiceWithBaz = $container->get('services.foo_service.with_baz');//[!code warning]

var_dump($fooServiceWithBaz->bat instanceof BatService);
// bool(true)

var_dump($fooServiceWithBaz->qux instanceof BazService);
// bool(true)

var_dump($fooServiceWithBaz->adminEmail);
// string(16) "admin@example.com
```

<<< @/_code/attribute-definition/autowire/FooService.php

<<< @/_code/attribute-definition/autowire/BarService.php

<<< @/_code/attribute-definition/autowire/BazService.php

<<< @/_code/attribute-definition/autowire/BatService.php

:::

## Внедрение зависимостей через сеттер-методы { #setups }

Параметр `$setups` атрибута `\Kaspi\DiContainer\Attributes\Autowire` объявляет внедрение зависимости через сеттер-методы PHP класса, используя атрибуты [`\Kaspi\DiContainer\Attributes\Setup`](setup.md) и [`\Kaspi\DiContainer\Attributes\SetupImmutable`](setup-immutable.md).

Значение `null` в параметре `$setups` указывает контейнеру на необходимость внедрять зависимости через атрибут `Setup` и/или атрибут `SetupImmutable` указанные у методов класса.

Значение `[]` (пустой массив) в параметре `$setups` указывает контейнеру не внедрять зависимости через сеттер-методы, даже если у методов класса указаны атрибуты `Setup` и `SetupImmutable`.

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as Parameter;

#[Autowire(
    setups: [//[!code highlight]
        'bar' => new Setup(//[!code ++]
            new Parameter('params.api_key')//[!code ++]
       ),//[!code ++]

        'baz' => new SetupImmutable(//[!code ++]
            new DiGet(Baz::class)//[!code ++]
        ),//[!code ++]
    ]//[!code highlight]
)]
final class Foo
{
    // …
    
    public function bar(string $apiKey): void
    {
        // …
    }
    
    public function baz(QuxInterface $qux): void
    {
        // …
    }
}
```

## Теги { #tags }

Параметр `$tags` атрибута `\Kaspi\DiContainer\Attributes\Autowire` устанавливает тег и метаданные для объявляемого PHP класса, используя [атрибут `\Kaspi\DiContainer\Attributes\Tag`](tag.md).

Значение `null` в параметре `$tags` указывает контейнеру на необходимость установить тег из атрибута `Tag` указанного в объявляемом PHP классе.

Значение `[]` (пустой массив) в параметре `$tags` указывает контейнеру не устанавливать тег к объявляемому PHP классу, даже если у класса указан атрибут `Tag`.

```php
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire(
    tags: [//[!code highlight]
        new Tag('tags.fuze'),//[!code ++]
        new Tag('tags.interbal_service'),//[!code ++]
    ]//[!code highlight]
)]
final class Foo
{
    // …
}
```

## Внедрения зависимости в параметр метода { #autowire-on-param }

Объявить внедрение PHP класса в параметр метода или `callable` типа можно через атрибут `\Kaspi\DiContainer\Attributes\Autowire`.

Значение в параметре `$id` атрибута `Autowire` может быть указано пустой строкой или строкой FQCN [^FQCN].

Пустая строка в параметре `$id` атрибута `Autowire` интерпретируется контейнером как объявление внедрения PHP класса исходя из типа параметра[^TypeHints] для которого применяется атрибут. 

🚧🚧🚧🚧

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
