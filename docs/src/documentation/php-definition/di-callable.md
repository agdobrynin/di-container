---
outline: [2, 4]
---
# diCallable()

## Обзор { #overview }

Хелпер функция `diCallable()` внедряет зависимость через [вызываемый тип `callable`](https://www.php.net/manual/en/language.types.callable.php).

Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\{
    DiDefinitionArgumentsInterface as Args,
    DiDefinitionTagArgumentInterface as Tag,
};

\Kaspi\DiContainer\diCallable(
    callable $definition,
    ?bool $isSingleton = null
): Args & Tag
```

Параметры:
- `$definition` – вызываемый тип.
- `$isSingleton` – возвращать один и тот же результат (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

Функция `diCallable()` возвращает объект предоставляющий методы:
- `bindArguments()` – указать аргументы для параметров функции.
- `bindTag()` – добавляет тег с метаданными для определения.

## bindArguments()

Передача аргументов для параметров вызываемого типа (_функции_).

<!--@include: ./_include/bind_arguments.md-->

### Пример внедрения зависимости через метод bindArguments() { #example-bind-arguments }

PHP классы:

::: code-group
<<< @/_code/php-definition/di-callable/Baz.php

<<< @/_code/php-definition/di-callable/Bar.php

<<< @/_code/php-definition/di-callable/Foo.php
:::

В файле конфигураций `callable_entries.php` необходимо объявить PHP класс `\App\Services\Foo` и указать внедрение зависимости в параметр `$value` через вызываемый тип[^CallableType] – `\App\Services\Baz::factoryMethod()`.

Для метода `\App\Services\Baz::factoryMethod()` объявить внедрение скалярного типа в параметр `$name` через метод `bindArguments()`, в параметр `$bar` контейнер внедрит зависимость самостоятельно, на основании конфигурации.

::: code-group

<<< @/_code/php-definition/di-callable/container.php

<<< @/_code/php-definition/di-callable/callable_entries.php

:::

Выполните в терминале команду:

```shell
php /app/src/container.php
```

Появится сообщение вида:

```console
string(26) "Lorem ipsum dolor sit amet"
```

## bindTag()

Теги позволяют отнести объявляемый вызываемый тип к коллекции элементов отмеченных одним тегом.

<!--@include: _include/bind_tag.md-->

## Идентификатор контейнера { #container-id }

Чтобы обеспечить доступность вызываемого типа через метод контейнера `get()` и `has()` необходимо указать в файле конфигураций соответствующий идентификатор контейнера[^ContainerId].

PHP классы:

::: code-group

<<< @/_code/php-definition/di-callable/Baz.php

<<< @/_code/php-definition/di-callable/Bar.php

:::

Метод контейнера `get('baz.factory_method')` должен возвращать результат выполнения вызываемого типа `\App\Services\Baz::factoryMethod()`.

Для метода `\App\Services\Baz::factoryMethod()` объявить внедрение скалярного типа в параметр `$name` через метод bindArguments(), в параметр `$bar` контейнер внедрит зависимость самостоятельно, на основании конфигурации.

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php// [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load('/app/config/factory.php')// [!code ++]
    ->build();

$result = $container->get('baz.factory_method');// [!code highlight]

var_dump($result);
```

```php [factory.php]
<?php
declare(strict_types=1);

// file: /app/config/factory.php // [!code highlight]

use App\Services\Baz;

use function Kaspi\DiContainer\diCallable;

return static function(): Generator {
    yield 'baz.factory_method' => diCallable([Baz::class, 'factoryMethod'])//[!code ++]
        ->bindArguments(//[!code ++]
            name: 'Lorem ipsum'//[!code ++]
    );//[!code ++]
};
```

:::

Выполните в терминале команду:

```shell
php /app/src/container.php
```

Появится сообщение вида:

```console
string(26) "Lorem ipsum dolor sit amet"
```

## Преобразуемые объявления { #convert-to-di-callable } 

В файлах конфигураций можно указать вызываемый тип[^CallableType] «как-есть», при условии что контейнер сможет самостоятельно внедрить требуемые зависимости на основании конфигурации контейнера.

### 🚧🚧🚧🚧

### Пример объявления callable типов «как-есть» { #example-convert-to-di-callable }

PHP классы и функции:

::: code-group

```php [ServiceFoo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/ServiceFoo.php// [!code highlight]

namespace App\Services;

use function getenv;

class ServiceFoo
{
    public function __construct(
        public readonly string $apiKey,
        public readonly bool $debug
    ) {}

    public static function makeForLocal(bool $debug = true): self
    {
        $apiKey = getenv('APP_API_KEY_LOCAL');

        return new self($apiKey, $debug)
    }
}
```

```php [functions.php]
<?php
declare(strict_types=1);

// file: /app/src/Functions/functions.php// [!code highlight]

namespace App\Functions;

use App\Services\ServiceFoo;

use function getenv;

function staging_service(bool $debug = false): ServiceOne {
    $apiKey = getenv('APP_API_KEY_STAGING');

    return new ServiceFoo($apiKey, $debug);
}

```

```php [Env.php]
<?php
declare(strict_types=1);

// file: /app/src/Helpers/Env.php// [!code highlight]

namespace App\Helpers;

use function getenv;

class Env
{
    // …

    public static function apiKey(): string
    {
        return match (getenv('APP_ENV')) {
            'dev' => getenv('APP_API_KEY_LOCAL'),
            'staging' => getenv('APP_API_KEY_STAGING'),
            'prod' => getenv('APP_API_KEY_PROD'),
        };
    }
}
```

:::

В файле конфигураций `callable_as_is.php` необходимо объявить три идентификатора контейнера `'services.foo.one'`, `'services.foo.two'`, `'services.foo.three'`. Метод контейнера `get()` обращаясь по этим идентификаторам должен получать объект PHP класса `\App\Services\ServiceFoo` с различными значениями свойств `$apiKey` и `$debug`.

::: code-group

```php [callable_as_is.php]
<?php
declare(strict_types=1);

// file: /app/config/callable_as_is.php// [!code highlight]

use App\Services\ServiceFoo;

return static function (): Generator {
   // 1️⃣ анонимная функция
   yield 'services.foo.one' => static fn () => new ServiceFoo(
        apiKey: 'qwerty-value',
        debug: false
    );

   // 2️⃣ статический метод класса
   yield 'services.foo.two' => [ServiceFoo::class, 'makeForLocal'];
   
   // 3️⃣ функция
   yield 'services.foo.three' => 'App\Functions\staging_service';
};
```

:::

Объявления `callable` типов в файле конфигураций `/app/config/callable_as_is.php` будут автоматически конвертированы в хелпер функцию `diCallable()`.

<!--@include: ../_include/term_notes.md-->
