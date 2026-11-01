---
outline: [2, 4]
---
# Autowire

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` объявляет PHP класс в контейнере, или [внедряет зависимость в параметр метода](#autowire-on-param).

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
- `$resetter` – конфигурация сброса состояния объекта.
- `$isLazy` – объявить «ленивый объект». 

> [!TIP]
> Изучите разделы документации:
> - [Внедрение «ленивых» объектов контейнером](../14-lazy-injection.md).
> - [Сброс состояния объектов](../12-object-resetters.md).

## Идентификатор контейнера { #container-id }

При объявлении PHP класса через атрибут `\Kaspi\DiContainer\Attributes\Autowire` пустая строка в параметре `$id` интерпретируется контейнером как идентификатор контейнера представленный FQCN[^FQCN] объявляемого PHP класса.

Объявить PHP класс `App\Services\Foo` через атрибут, идентификатор контейнера должен быть строкой `'App\Services\Foo'`, и объявить внедрение скалярного значения `'Lorem ipsum'` в параметр конструктора класса `$value`.

::: code-group
<<< @/_code/attribute-definition/autowire/Foo.php
:::

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

$foo = $container->get(Foo::class);//[!code highlight]

var_dump($foo->value);
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(11) "Lorem ipsum"
```

### Объявление PHP класса с несколькими идентификаторами { #container-id-multuple }

Атрибут `\Kaspi\DiContainer\Attributes\Autowire` может объявлять один и тот же PHP класс в контейнере с разными идентификаторами[^ContainerId].

Объявить PHP класс `App\Services\Bar` через атрибут, идентификатор контейнера должен быть представлен строкой `'App\Services\Bar'`, и объявить внедрение скалярного значения `10` в параметр конструктора `$value`.

Объявить PHP класс `App\Services\Bar` через атрибут, идентификатор контейнера должен быть представлен строкой `'services.bar_20'`, и объявить внедрение скалярного значения `20` в параметр конструктора `$value`.

::: code-group
<<< @/_code/attribute-definition/autowire/Bar.php
:::

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

$bar = $container->get(Bar::class);
var_dump($bar->value);


$bar20 = $container->get('services.bar_20');
var_dump($bar20->value);
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
int(10)
int(20)
```


## Аргументы конструктора PHP класса { #arguments }

Параметр `$arguments` атрибута `\Kaspi\DiContainer\Attributes\Autowire` передает аргументы конструктору PHP класса.

<!--@include: _include/arguments.md-->

<!--@include: _include/to_arguments.md-->

### Пример объявления аргументов конструктору { #example }

PHP классы:

::: code-group

<<< @/_code/attribute-definition/autowire/FooService.php

<<< @/_code/attribute-definition/autowire/BarService.php

<<< @/_code/attribute-definition/autowire/BazService.php

<<< @/_code/attribute-definition/autowire/BatService.php

<<< @/_code/attribute-definition/autowire/Interfaces/QuxInterface.php

::: 

Необходимо объявить PHP класс `\App\Services\FooService` с разными идентификаторами контейнера и аргументами.

::: code-group
<<< @/_code/attribute-definition/autowire/FooServiceAttr.php [FooService.php]
:::

::: code-group
```php [container_build.php]
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

var_dump($fooService->bat::class);
var_dump($fooService->qux::class);
var_dump($fooService->adminEmail);

$fooServiceWithBaz = $container->get('services.foo_service.with_baz');//[!code warning]

echo PHP_EOL;

var_dump($fooServiceWithBaz->bat::class);
var_dump($fooServiceWithBaz->qux::class);
var_dump($fooServiceWithBaz->adminEmail);
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(23) "App\Services\BatService"
string(23) "App\Services\BarService"
string(19) "manager@example.com"

string(23) "App\Services\BatService"
string(23) "App\Services\BazService"
string(16) "admin@example.com
```

## Внедрение зависимостей через сеттер-методы { #setups }

Параметр `$setups` атрибута `\Kaspi\DiContainer\Attributes\Autowire` объявляет внедрение зависимости через сеттер-методы PHP класса, используя атрибуты [`\Kaspi\DiContainer\Attributes\Setup`](setup.md) и [`\Kaspi\DiContainer\Attributes\SetupImmutable`](setup-immutable.md).

Значение `null` в параметре `$setups` указывает контейнеру на необходимость внедрять зависимости через **атрибуты `Setup` и `SetupImmutable` указанные у методов класса**.

Значение `[]` (пустой массив) в параметре `$setups` указывает контейнеру **не внедрять зависимости через сеттер-методы**, даже если у методов класса указаны атрибуты `Setup` и `SetupImmutable`.

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
    
    public function baz(QuxInterface $qux): self
    {
        // …
    }
}
```

## Теги { #tags }

Параметр `$tags` атрибута `\Kaspi\DiContainer\Attributes\Autowire` устанавливает тег и метаданные для объявляемого PHP класса, используя [атрибут `\Kaspi\DiContainer\Attributes\Tag`](tag.md).

Значение `null` в параметре `$tags` указывает контейнеру на необходимость **установить тег через атрибут `Tag` указанный в объявляемом PHP классе**.

Значение `[]` (пустой массив) в параметре `$tags` указывает контейнеру **не устанавливать тег к объявляемому PHP классу**, даже если у класса указан атрибут `Tag`.

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

Объявить внедрение PHP класса в параметр метода или вызываемого тип[^CallableType] можно через атрибут `\Kaspi\DiContainer\Attributes\Autowire`.

Значение в параметре `$id` атрибута `Autowire` может быть указано пустой строкой или строкой FQCN[^FQCN].

Пустая строка в параметре `$id` атрибута `Autowire` будет интерпретирована конфигуратором как тип параметра[^TypeHints] для которого применяется атрибут.

### Пример объявления внедрения в параметр метода { #example-autowire-on-param }

PHP классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

class Foo
{
    public function __construct(
        public readonly QuxInterface $qux,
        public readonly Baz $baz,
    ) {}
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface 
{
    public function __construct(public readonly string $notifyEmail) {}
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php //[!code highlight]

namespace App\Services;

final class Baz
{
    public function __construct(
        public readonly string $email = 'baz@localhost'
    ) {}
}
```

<<< @/_code/attribute-definition/autowire/Interfaces/QuxInterface.php

:::

Объявить внедрение в параметры конструктора `$qux` и `$baz` через атрибут `Kaspi\DiContainer\Attributes\Autowire` PHP класса `\App\Services\Foo`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

class Foo
{
    public function __construct(
        #[Autowire(
            id: Bar::class,// [!code ++]
            arguments: [// [!code ++]
                new DiParameter('emails.bar')// [!code ++]
            ]// [!code ++]
        )]
        public readonly QuxInterface $qux,

        #[Autowire(
            arguments: [// [!code ++]
                new DiParameter('emails.baz')// [!code ++]
            ]// [!code ++]
        )]
        public readonly Baz $baz,
    ) {}
}
```

:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->addParameters([
        'emails.bar' => 'manager@example.com',//[!code highlight]
        'emails.baz' => 'admin@example.com',//[!code highlight]
    ])
    ->build();

$foo = $container->get(Foo::class);//[!code warning]

var_dump($foo->qux->notifyEmail);
var_dump($foo->baz->email);
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(19) "manager@example.com"
string(16) "admin@example.com"
```

<!--@include: ../_include/term_notes.md-->
