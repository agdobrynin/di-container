---
outline: [2, 4]
---
# 🚫 Исключение элементов из коллекции { #title }

## Обзор { #overview }

Коллекции могу исключать собираемые элементы. Исключить из коллекции можно вызывающий PHP класс т.е. самого себя, или по идентификатору контейнера присвоенному элементу коллекции.

## Исключение вызывающего класса { #exclide-self }

В коллекцию элементов может попадать PHP класс внедряющий эту же коллекцию. Для исключения такого поведения обратите внимание на параметр `$selfExclude`
в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) и [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md). По умолчанию значение этого параметра `true`.

Если необходимо чтобы вызывающий PHP класс так же был добавлен в коллекцию необходимо указать в параметр `$selfExclude` значение `false`.

::: code-group

```php [хелпер функция diTaggedAs()]
// file: /app/config/services.php
use App\Services\ClassTaggedAs;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function ():\Generator {

    yield diAutowire(ClassTaggedAs::class)
        ->bindTag('tags.foo')
        ->bindArguments(
            items: diTaggedAs('tags.foo', selfExclude: false)
        );
};
```

```php [атрибут TaggedAs]
// file: /app/src/Services/ClassTaggedAs.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\{Tag, TaggedAs}; 

#[Tag('tags.foo')]
final class ClassTaggedAs
{
    public function __construct(
        #[TaggedAs('tags.foo', selfExclude: false)]
        public readonly iterable $items
    ) {}
}
```

:::

## Исключение из коллекции по идентификатору контейнера { #exclude-by-id }

Для исключения элементов из коллекции используйте параметр `$containerIdExclude`
в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) и [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

Параметр `$containerIdExclude` должен быть представлен массивом содержащим идентификаторы контейнера исключённых из коллекции.

### Пример настройки исключений из коллекции элементов { #example-exclude-by-id }

PHP классы `\App\Service\Bar`, `\App\Service\Baz`, `\App\Service\Qux` при конфигурации получили имя тега `'tags.internal_service'`.

В параметр `\App\Service\Foo::$services` необходимо внедрить коллекцию элементов отмеченных тегом `'tags.internal_service'`, но исключить из полученной коллекции элемент у которого идентификатор контейнера является строка `'App\Services\Baz'`, т.е. сервис получаемый через метод контейнера `$container->get('App\Services\Baz')`.  

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Service;

final class Foo
{
    public function __construct(public readonly iterable $services) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Service;

final class Bar
{
    // …    
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Service;

final class Baz
{
    // …    
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Service;

final class Qux
{
    // …    
}
```

:::

#### Настройки в стиле PHP определений { #example-exclude-by-id-php-definitions }

Конфигурационный файл:

```php
// file: /app/config/services.php
use Generator;
use App\Services\{Foo, Bar, Baz, Qux};

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            services: diTaggedAs(
                'tags.internal_service',
                containerIdExclude: [Baz::class]
            )
        );
        
    yield diAutowire(Bar::class)
        ->bindTag('tags.internal_service');
        
    yield diAutowire(Baz::class)
        ->bindTag('tags.internal_service');
        
    yield diAutowire(Qux::class)
        ->bindTag('tags.internal_service');
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// string(16) "App\Services\Bar"
// string(16) "App\Services\Qux"
```

Класс `\App\Services\Baz` не попал в коллекцию так как его идентификатор контейнера `'App\Services\Baz'` исключен из коллекции
хелпер функцией `diTaggedAs()` в параметре `$containerIdExclude`.

#### Настройки через PHP атрибуты { #example-exclude-by-id-php-attributes }

Конфигурация PHP классов атрибутами `\Kaspi\DiContainer\Attributes\Tag` и `\Kaspi\DiContainer\Attributes\TaggedAs`:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Service;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Foo
{
    public function __construct(
        #[TaggedAs(
            name: 'tags.internal_service',
            containerIdExclude: [Baz::class],
        )]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Service;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.internal_service')]
final class Bar
{
    // …    
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Service;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.internal_service')]
final class Baz
{
    // …    
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Service;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.internal_service')]
final class Qux
{
    // …    
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// string(16) "App\Services\Bar"
// string(16) "App\Services\Qux"
```

Класс `\App\Services\Baz` не попал в коллекцию так как его идентификатор контейнера `'App\Services\Baz'` исключен из коллекции в параметре атрибута `\Kaspi\DiContainer\Attributes\TaggedAs::$containerIdExclude`.
