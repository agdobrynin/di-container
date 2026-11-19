---
outline: [3, 4]
title: Dependency injection into a union-typed parameter
---
### Dependency injection into a union-typed parameter { #overview }

При внедрении в параметр [объединенного типа](https://www.php.net/manual/en/language.types.type-system.php#language.types.type-system.composite.union) на основе конфигурации контейнера производится попытка найти доступные объявления PHP-классов, и если будет найдено несколько логически равнозначных вариантов внедрения зависимости то будет выброшено исключение,
которое сообщит о необходимости уточнить тип внедряемого аргумента.

Ниже рассмотрим пример как объявить внедрение для параметра объединённого типа.

### PHP-классы { #php-classes }

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

final class Foo
{ 
    public function __construct(
        public readonly Bar|Baz $dependency
    ) {}

}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

final class Baz
{
    // …
}
```

:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->build();

$foo = $container->get(Foo::class);//[!code error]

var_dump($foo->dependency); 
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Будет выброшено исключение:

> [!DANGER] PHP Fatal error:
> Uncaught Kaspi\DiContainer\Exception\AutowireParameterTypeException:
> Cannot automatically resolve dependency in App\Services\Foo::__construct().
> Please specify the Parameter #0 [ \<required\> App\Services\Bar | App\Services\Baz $dependency ].

**Для устранения ошибки необходимо конкретизировать тип аргумента для параметра конструктора `$dependency` PHP-класса `\App\Services\Foo`.**

#### Объявление в файлах конфигураций { #php-definitions }

В файле конфигураций `services.php` через [хелпер функцию `diGet()`](../documentation/php-definition/di-get.md) конкретизируем какую именно зависимость необходимо внедрить в параметре конструктора `$dependency` PHP-класса `\App\Services\Foo`.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->dependency); 
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Service\{Foo, Baz};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(//[!code ++]
            dependency: diGet(Baz::class)//[!code ++]
        );//[!code ++]
};
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
  object(App\Services\Baz)#2 (0) {
  }
```

#### Объявление через атрибут { #php-attributes }

Через [атрибут `\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) конкретизируем какую именно зависимость необходимо внедрить в параметре конструктора `$dependency` PHP-класса `\App\Services\Foo`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Inject;//[!code ++]

final class Foo
{ 
    public function __construct(
        #[Inject(Baz::class)]//[!code ++]
        public readonly Bar|Baz $dependency
    ) {}

}
```
:::


::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->dependency); 
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
  object(App\Services\Baz)#2 (0) {
  }
```
