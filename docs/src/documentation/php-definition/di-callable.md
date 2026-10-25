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

Необходимо через метод `bindArguments()` указать вызываемому методу `\App\Services\Baz::factoryMethod()` как внедрят зависимость в параметр `$name`.

::: code-group

<<< @/_code/php-definition/di-callable/callable_entries.php

<<< @/_code/php-definition/di-callable/container.php

<<< @/_code/php-definition/di-callable/Baz.php

<<< @/_code/php-definition/di-callable/Bar.php

<<< @/_code/php-definition/di-callable/Foo.php

:::

## bindTag()

Теги позволяют отнести объявляемый вызываемый тип к коллекции элементов отмеченных одним тегом.

<!--@include: _include/bind_tag.md-->

## Идентификатор контейнера { #container-id }

Чтобы обеспечить доступность вызываемого типа через метод контейнера `get()` и `has()` необходимо указать в файле конфигураций соответствующий идентификатор контейнера[^ContainerId].

::: code-group

```php [callable_services.php]
<?php
declare(strict_types=1);

// file: /app/config/callable_services.php// [!code highlight]

use App\Services\Baz;

use function Kaspi\DiContainer\diCallable;

return static function(): Generator {
    yield 'baz.factory_method' => diCallable([Baz::class, 'factoryMethod'])//[!code ++]
        ->bindArguments(//[!code ++]
            name: 'Lorem ipsum'//[!code ++]
    );//[!code ++]
};
```

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php// [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/callable_services.php')// [!code highlight]
    ->build();

$result = $container->get('baz.factory_method');// [!code highlight]
$foo = new Foo($result);

var_dump($foo->value);
// string(26) "Lorem ipsum dolor sit amet"
```

<<< @/_code/php-definition/di-callable/Baz.php

<<< @/_code/php-definition/di-callable/Bar.php

<<< @/_code/php-definition/di-callable/Foo.php

:::

## Преобразуемые объявления { #convert-to-di-callable } 

В файлах конфигураций можно указать вызываемый тип «как-есть», при условии что контейнер сможет самостоятельно внедрить требуемые зависимости на основе конфигурации контейнера.

::: code-group

```php [services.php]
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
   yield 'services.foo.two' => [ServiceFoo::class, 'makeWithDebug'];
   
   // 3️⃣ функция
   yield 'services.foo.three' => 'App\Functions\staging_service';
};
```

```php [ServiceFoo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/ServiceFoo.php// [!code highlight]

namespace App\Services;

class ServiceFoo
{

    public function __construct(
        public readonly string $apiKey,
        public readonly bool $debug
    ) {}


    public static function makeWithDebug(string $apiKey): self
    {
        return new self($apiKey, true)
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

function staging_service(bool $debug = true): ServiceOne {
    $apiKey = getenv('APP_API_KEY_STAGING');

    return new ServiceFoo($apiKey, $debug);
}

```

:::

Объявления `callable` типов в файле конфигураций `/app/config/callable_as_is.php` будут автоматически конвертированы в хелпер функцию `diCallable()`.

<!--@include: ../_include/term_notes.md-->
