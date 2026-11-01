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

Чтобы обеспечить доступность вызываемого типа через метод контейнера `get()` и `has()` необходимо указать в файле конфигураций соответствующий идентификатор контейнера[^ContainerId] и объявить `callable` тип.


### Пример объявления с идентификатором { #example-container-id }
PHP классы:

::: code-group

<<< @/_code/php-definition/di-callable/Baz.php

<<< @/_code/php-definition/di-callable/Bar.php

:::

Метод контейнера `get('baz.factory_method')` должен возвращать результат выполнения вызываемого типа `\App\Services\Baz::factoryMethod()`.

Для метода `\App\Services\Baz::factoryMethod()` объявить внедрение скалярного типа в параметр `$name` через метод `bindArguments()`, в параметр `$bar` контейнер внедрит зависимость самостоятельно, на основании конфигурации.

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

Объявление `callable` типа в файле конфигураций преобразуется контейнером в хелпер функцию `diCallable()`.

### Пример объявления callable типов «как-есть» { #example-convert-to-di-callable }

PHP классы и функции:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

use App\Helpers\Env;

use function getenv;

class Foo
{
    public function __construct(
        public readonly string $apiKey,
        public readonly bool $debug
    ) {}

    public static function makeForLocal(Env $env, bool $debug = true): self
    {
        $apiKey = $env::apiKey();

        return new self($apiKey, $debug);
    }
}
```

```php [functions.php]
<?php
declare(strict_types=1);

// file: /app/src/Functions/functions.php// [!code highlight]

namespace App\Functions;

use App\Services\Foo;
use App\Helpers\Env;

use function getenv;

function staging_service(Env $env, bool $debug = false): Foo {
    $apiKey = $env::apiKey();

    return new Foo($apiKey, $debug);
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
        $apiKey = match (getenv('APP_ENV')) {
            'dev' => getenv('APP_API_KEY_LOCAL'),
            'staging' => getenv('APP_API_KEY_STAGING'),
            'prod' => getenv('APP_API_KEY_PROD'),
        };
        
        // $apiKey calculation
        
        return $apiKey;
    }
}
```

:::

В файле конфигураций `callable_as_is.php` необходимо объявить три идентификатора контейнера:
- `'services.foo_from_anonymous_fn'` – анонимная функция.
- `'services.foo_from_static_method'` – статический метод класса.
- `'services.foo_from_function'` – функция.

Метод контейнера `get()` обращаясь по этим идентификаторам должен получать объект PHP класса `\App\Services\Foo` с различными значениями свойств `$apiKey` и `$debug`.

::: code-group 

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php// [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;

putenv('APP_ENV=dev');// [!code highlight]
putenv('APP_API_KEY_LOCAL=FOO-BAR');// [!code highlight]
putenv('APP_API_KEY_STAGING=BAR-BAZ');// [!code highlight]
putenv('APP_API_KEY_PROD=BAZ-QUX');// [!code highlight]

$container = (new DiContainerBuilder())
    ->load('/app/config/callable_as_is.php')// [!code ++]
    ->build();

$fooFromFn = $container->get('services.foo_from_anonymous_fn');
var_dump($fooFromFn);

$fooFromStaticMethod = $container->get('services.foo_from_static_method');
var_dump($fooFromStaticMethod);

$fooFromFunction = $container->get('services.foo_from_function');
var_dump($fooFromStaticMethod);
```

```php [callable_as_is.php]
<?php
declare(strict_types=1);

// file: /app/config/callable_as_is.php// [!code highlight]

use App\Services\Foo;

return static function (): Generator {
   yield 'services.foo_from_anonymous_fn' => static fn () => new Foo(
        apiKey: 'qwerty-value',
        debug: false
    );

   yield 'services.foo_from_static_method' => [ServiceFoo::class, 'makeForLocal'];
   
   yield 'services.foo_from_function' => 'App\Functions\staging_service';
};
```

:::

Выполните в терминале команду:

```shell
php /app/src/container.php
```

Появится сообщение вида:

```console
object(App\Services\Foo)#1 (2) {
  ["apiKey"]=>
  string(12) "qwerty-value"
  ["debug"]=>
  bool(false)
}

object(App\Services\Foo)#1 (2) {
  ["apiKey"]=>
  string(7) "FOO-BAR"
  ["debug"]=>
  bool(true)
}

object(App\Services\Foo)#1 (2) {
  ["apiKey"]=>
  string(7) "FOO-BAR"
  ["debug"]=>
  bool(false)
}
```


<!--@include: ../_include/term_notes.md-->
