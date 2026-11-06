---
outline: [2,4]
---
# Аргументы для метода фабрики { #title }

## Обзор { #overview }

При объявлении внедрения зависимости или объявлении PHP-класса через PHP-класса «фабрика»[^FactoryPattern], параметры конструктора и метода PHP-класса «фабрика» могут быть внедрены автоматически на основании конфигурации контейнера.

Для особых сценариев можно указать методу PHP-класса «фабрика» как внедрять зависимости в конкретные параметры.

При объявлении внедрения в [файлах конфигураций](../container-builder/configuration_files.md) аргументы передаются в метод `bingArguments()` [хелпер функции diFactory()](../php-definition/di-factory.md#bindarguments).

При объявлении внедрения через [атрибут `\Kaspi\DiContainer\Attributes\DiFactory`](../attribute-definition/di-factory.md#args) аргументы указываются в параметре `$arguments`.

## Пример указания аргументов методу PHP-класса «фабрика» { #example }

PHP-классы:

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(private readonly string $value) {}
}

```
```php [Bar.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Bar.php// [!code highlight]

namespace App\Services;

final class Bar
{
    public function __construct(private readonly Baz $baz) {}
    
    public function createValue(string $prefixValue): string
    {
        return $prefixValue.' '.$this->baz->doSomething();
    }
}

```

```php [Baz.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

final class Baz
{
    // …

    public function doSomething(): string
    {
        return 'dolor sit amet';
    }
}
```
:::

Необходимо объявить внедрение в параметр конструктора `$value` PHP-класса `\App\Services\Foo` значения которое вычисляет метод `\App\Services\Bar::createValue()`.

В этом примере `\App\Services\Bar::createValue()` является «фабрикой» которой делегирован процесс создания требуемого значения.

В параметр конструктора `$baz` PHP-класса `\App\Services\Bar` контейнер внедрит зависимость самостоятельно, на основании конфигурации.

Для параметра `$prefixValue` фабричного метода `\App\Services\Bar::createValue()` объявить внедрение скалярного типа через [параметр контейнера](../container-parameters/index.md) с именем `'params.prefix_value'`.

### Объявление в файлах конфигураций { #example-php-definition }

В файле конфигураций `services.php` объявляем PHP-класс `\App\Services\Foo` и внедрение зависимости в параметр конструктора `$value` через [хелпер функцию `diFactory()`](../php-definition/di-factory.md).

Для параметра `$prefixValue` фабричного метода `\App\Services\Bar::createValue()` объявим внедрение скалярного значения через [хелпер функцию `diParameter()`](../php-definition/di-parameter.md).

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code ++]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->value);
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php// [!code highlight]

use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diFactory, diParameter};

return static function(): Generator {
    yield diAutowire(Foo::class)// [!code highlight]
        ->bindArguments(// [!code highlight]
            value: diFactory(['Bar::class', 'createValue'])// [!code ++]
                ->bindArguments(// [!code ++]
                    prefixValue: diParameter('params.prefix_value')// [!code ++]
                )// [!code ++]
        );// [!code highlight]
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

### Объявление через атрибут { #example-attribute }

Объявляем внедрение зависимости в параметр конструктора `$value` через [атрибут `DiFactory`](../attribute-definition/di-factory.md) PHP-класса `\App\Services\Foo`.

Для параметра `$prefixValue` фабричного метода `\App\Services\Bar::createValue()` объявим внедрение скалярного значения PHP-класс `\Kaspi\DiContainer\DiDefinition\DiDefinitionParameter`.

<!--@include: ../attribute-definition/_include/to_arguments.md-->

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\DiFactory;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;//[!code ++]

final class Foo
{
    public function __construct(
        #[DiFactory(//[!code ++]
            ['Bar::class', 'createValue'],//[!code ++]
            arguments: [//[!code ++]
                'prefixValue' => new DiParameter('params.prefix_value')//[!code ++]
            ],//[!code ++]
        )]//[!code ++]
        private readonly string $value
    ) {}
}

```
:::

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code ++]
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->value);
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

<!--@include: ../_include/term_notes.md-->
