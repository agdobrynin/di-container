---
outline: [2,4]
---
# Объявление внедрения зависимости через фабрику { #title }

## Обзор { #overview }

Объявить внедрение в параметр метода или вызываемого типа[^CallableType] можно через PHP-класс «фабрика».

<!--@include: _include/auto_inject_params.md-->

Объявление внедрения в параметры можно сделать:
- через [хелпер функцию `diFactory()`](../php-definition/di-factory.md) в файлах конфигураций.
- через [атрибут `\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md).

Важное замечание: фабричный метод[^FactoryPattern] должен возвращать результат совместимый с типом параметра[^TypeHints] метода или функции.


## Пример объявления внедрения зависимости через «фабрику» { #example }

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly Qux $qux
    ) {}    
}
```

<<< @/_code/attribute-definition/di-factory/Qux.php

<<< @/_code/attribute-definition/di-factory/Configurator.php

<<< @/_code/attribute-definition/di-factory/Bar.php

<<< @/_code/attribute-definition/di-factory/Baz.php

:::

Для PHP-класса `\App\Services\Foo` необходимо объявить внедрение зависимости в параметр `$qux` через фабрику `\App\Helpers\Configurator::quxDoConfig()`.

### Объявление в файлах конфигураций { #helper-fn-di-factory }

В файле конфигураций `services.php` зарегистрируем PHP-класс `\App\Services\Foo` в контейнере и настроим внедрение зависимости для параметра конструктора `$qux` с помощью хелпер функции `diFactory()`.

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

var_dump($container->get(Foo::class)->qux->value);
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php  // [!code highlight]

use App\Service\Foo;
use App\Helpers\Configurator;

use function Kaspi\DiContainer\{diAutowire, diFactory};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            qux: diFactory([Configurator::class, 'doConfigQux'])//[!code ++]        
        );
};
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(4) "Fuuz"
```

### Объявление через атрибут { #attribute-di-factory }

Объявить внедрение в параметр конструктора `$qux` PHP-класса `\App\Services\Foo` через атрибут `\Kaspi\DiContainer\Attributes\DiFactory`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Helpers\Configurator;//[!code ++]

final class Foo
{
    public function __construct(
        #[DiFactory([Configurator::class, 'doConfigQux'])]//[!code ++]
        public readonly Qux $qux
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
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

var_dump($container->get(Foo::class)->qux->value);
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(4) "Fuuz"
```

<!--@include: ../_include/term_notes.md-->
