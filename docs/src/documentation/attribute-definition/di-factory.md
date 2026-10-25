---
outline: [2, 4]
---
# DiFactory

## Обзор { #overview }

Объявление через атрибут `\Kaspi\DiContainer\Attributes\DiFactory` реализуется через паттерн «фабрика» [^FactoryPattern]. Атрибут объявляет PHP класс в контейнере, или [внедряет зависимость](#example-di-factory-param).

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

> [!TIP]
> Изучите раздел документации [«Использование фабричных методов для внедрения зависимостей»](../07-factory.md).

## Объявление PHP класса { #di-factory-class }

PHP класс можно объявить через атрибут `\Kaspi\DiContainer\Attributes\DiFactory`, фабричный метод[^FactoryPattern] должен возвращать объявляемый класс, обеспечивая доступ к объявляемому PHP классу через методы контейнера `get()` и `has()`.

### Пример объявления PHP класса через «»фабрику» { #di-factory-class-example }

Объявить PHP класс `\App\Services\Qux` через фабрику `\App\Helpers\Configurator::quxDoConfig()` – нестатический метод класса.

::: code-group

```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Helpers\Configurator;

#[DiFactory([Configurator::class, 'quxDoConfig'])]//[!code ++]
final class Qux
{
    public function __construct(public readonly string $value) {}
}
```

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
// (bool) true

$qux = $container->get(Qux::class);

var_dump($qux->value);
// string(4) "Fuuz"
// or
// string(2) "Ok"
```

<<< @/_code/attribute-definition/di-factory/Configurator.php

<<< @/_code/attribute-definition/di-factory/Bar.php

<<< @/_code/attribute-definition/di-factory/Baz.php

::: 

## Пример объявления внедрения зависимости через фабрику { #example-di-factory-param }

Необходимо внедрить зависимость через конструктор в параметр `$qux` PHP класса `\App\Services\Foo` используя фабрику `\App\Helpers\Configurator::quxDoConfig()` – нестатический метод класса.


::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use App\Helpers\Configurator;

final class Foo
{
    public function __construct(
        #[DiFactory([Configurator::class, 'quxDoConfig'])]//[!code ++]
        public readonly Qux $qux
    ) {}    
}
```

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

$foo = $container->get(Foo::class);

var_dump($foo->qux->value);
// string(4) "Fuuz"
// or
// string(2) "Ok"
```

<<< @/_code/attribute-definition/di-factory/Qux.php

<<< @/_code/attribute-definition/di-factory/Configurator.php

<<< @/_code/attribute-definition/di-factory/Bar.php

<<< @/_code/attribute-definition/di-factory/Baz.php

:::

<!--@include: ../_include/term_notes.md-->
