---
outline: [2,4]
title: Объявление PHP-класса
---
### Объявление PHP-класса { #title }

Для случаев когда объявление PHP-класса не может быть создано простым вызовом `new Foo()`, а требует особой сборки, то можно делегировать сборку этого класса специальному PHP-классу – «фабрика».

<!--@include: _include/auto_inject_params.md-->

Объявление PHP-класса через «фабрику» можно сделать:
- через [хелпер функцию `diFactory()`](../php-definition/di-factory.md) в файлах конфигураций.
- через [атрибут `\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md).

Важное замечание: фабричный метод[^FactoryPattern] должен возвращать объявляемый класс, обеспечивая доступ к объявляемому PHP-классу через методы контейнера `get()` и `has()`.

### Пример объявления PHP-класса через «фабрику» { #di-factory-class-example }

PHP-классы:

::: code-group

<<< @/_code/attribute-definition/di-factory/Qux.php

<<< @/_code/attribute-definition/di-factory/Configurator.php

<<< @/_code/attribute-definition/di-factory/Bar.php

<<< @/_code/attribute-definition/di-factory/Baz.php

:::

Объявить в контейнере PHP-класс `\App\Services\Qux` через фабрику `\App\Helpers\Configurator::quxDoConfig()`.

#### Объявление в файлах конфигураций { #example-helper-fn-di-factory }

В файле конфигураций `services.php` зарегистрируем PHP-класс `\App\Services\Qux` в контейнере, используя хелпер функцию `diFactory()`.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Qux;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

var_dump($container->has(Qux::class));
var_dump($container->get(Qux::class)->value);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Services\Qux;

use function Kaspi\DiContainer\diFactory;

return static function (): Generator {
    yield Qux::class => diFactory([Configurator::class, 'doConfigQux']); //[!code ++]
};
```

::: 

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
(bool) true
string(4) "Fuuz"
```

#### Объявление через атрибут { #example-attribute-di-factory }

Объявление PHP-класса `\App\Services\Qux` в контейнере через атрибут `\Kaspi\DiContainer\Attributes\DiFactory`.

::: code-group

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Helpers\Configurator;//[!code ++]

#[DiFactory([Configurator::class, 'doConfigQux'])]//[!code ++]
final class Qux
{
    public function __construct(public readonly string $value) {}
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
use App\Services\Qux;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

var_dump($container->has(Qux::class));
var_dump($container->get(Qux::class)->value);
```

:::


Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
(bool) true
string(4) "Fuuz"
```

<!--@include: ../_include/term_notes.md-->
