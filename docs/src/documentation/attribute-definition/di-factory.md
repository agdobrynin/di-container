---
outline: [2, 4]
---
# DiFactory

## Обзор { #overview }

Объявление через атрибут `\Kaspi\DiContainer\Attributes\DiFactory` реализуется через паттерн «фабрика» [^FactoryPattern]. Атрибут объявляет PHP-класс в контейнере, или [внедряет зависимость](#di-factory-param).

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\DiFactory::__construct(
    string|array $definition,
    ?bool $isSingleton = null,
    array $arguments = []
)
```

Параметры:
- `$definition` – FQCN[^FQCN] и имя метода.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments` – аргументы метода фабрики.

### Аргументы метода фабрики { #args }

Параметр `$arguments` атрибута `\Kaspi\DiContainer\Attributes\DiFactory` передает аргументы методу фабрики.

<!--@include: _include/arguments.md-->

<!--@include: _include/to_arguments.md-->

> [!TIP]
> Изучите раздел документации [«Использование фабричных методов для внедрения зависимостей»](../factories/index.md).

## Объявление PHP-класса { #di-factory-class }

PHP-класс можно объявить через атрибут `\Kaspi\DiContainer\Attributes\DiFactory`, фабричный метод[^FactoryPattern] должен возвращать объявляемый класс, обеспечивая доступ к объявляемому PHP-классу через методы контейнера `get()` и `has()`.

### Пример объявления PHP-класса через «фабрику» { #di-factory-class-example }

PHP-классы:

::: code-group
```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php

namespace App\Services;

final class Qux
{
    public function __construct(public readonly string $value) {}
}
```

<<< @/_code/attribute-definition/di-factory/Configurator.php

<<< @/_code/attribute-definition/di-factory/Bar.php

<<< @/_code/attribute-definition/di-factory/Baz.php

:::

Объявить PHP-класс `\App\Services\Qux` через фабрику `\App\Helpers\Configurator::quxDoConfig()` – нестатический метод класса.

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
    ->import(namespace: 'App\\', src: '/app/src')
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

## Объявление внедрения зависимости через фабрику { #di-factory-param }

Объявить внедрение в параметр метода или функции можно через атрибут `\Kaspi\DiContainer\Attributes\DiFactory`. Необходимо чтобы результат выполнения фабрики был совместим с типом параметра метода или функции.

### Пример объявления внедрения зависимости через фабрику { #example-di-factory-param }

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

Для PHP-класса `\App\Services\Foo` необходимо объявить внедрение зависимости в параметр `$qux` через фабрику `\App\Helpers\Configurator::quxDoConfig()` – нестатический метод класса.


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
    ->import(namespace: 'App\\', src: '/app/src')
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
