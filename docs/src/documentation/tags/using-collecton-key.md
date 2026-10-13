---
outline: [2, 4]
---
# 🔑 Использование ключей коллекции { #title }

## Обзор { #overview }

Внедряемая коллекция по умолчанию в качестве ключей элементов получает идентификатор контейнера [^ContainerId] этого элемента, что позволяет обращаться к элементам коллекции в [произвольном порядке](#key-access).

Это поведение можно изменить:
- через [отключение строковых ключей в коллекции](#key-as-int).
- через [метаданные тега](#key-in-metadata).
- через [«Метод-ключа»](#key-in-method) для элемента коллекции PHP класс.
- через [«Метод-ключа» по умолчанию](#key-in-method-default) для элемента коллекции PHP класс.

> [!IMPORTANT]
> Если в коллекции встречаются одинаковые ключи у элементов, то в коллекцию попадёт элемент с более высоким приоритетом, определения с таким же значением ключа будут игнорированы.
> Изучите раздел «[Совпадение ключей элементов в коллекции](#keys-equals)».

> [!WARNING]
> При обращении к элементу коллекции по ключу может быть выброшено исключение с типом `Psr\Container\NotFoundExceptionInterface`, если элемент коллекции не найден по запрошенному ключу.

## Произвольный доступ к элементам коллекции { #key-access }

При внедрении коллекции через [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) параметр `$isLazy` по умолчанию имеет значение `true`,
что означает – внедряемый объект будет «[ленивой коллекцией](index.md#collection-is-lazy)».

«Ленивая коллекция» реализует интерфейсы:
- `\Iterator`
- `\Psr\Container\ContainerInterface`
- `\ArrayAccess`
- `\Countable`

Реализация этих интерфейсов даёт возможность:
- Перебор элементов коллекции через [языковую конструкцию `foreach`](https://www.php.net/manual/en/control-structures.foreach.php).
- Доступ к элементу по имени ключа в стиле PHP массивов – `$service['foo.name']`.
- Доступ к элементу через методы `get()` и `has()` описанные в [PSR-11](https://www.php-fig.org/psr/psr-11/).

### Пример внедрения коллекции { #example-inject-lazy-collection-with-key-assess }

PHP классы:

::: code-group

```php [Foo.php]
//file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

:::

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где ключами элементов будут выступать идентификатор контейнера [^ContainerId] элемента.

#### Настройка в файлах конфигураций { #example-inject-lazy-collection-with-key-assess-php-definitions }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md), добавим теги PHP классам через метод `bindTag()` [^BindTag].

Конфигурационный файл:

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.access_by_key')
        );

    yield diAutowire(Bar::class)
        ->bindTag(name: 'tags.access_by_key');
   
   yield diAutowire(Baz::class)
        ->bindTag(name: 'tags.access_by_key');
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};
use function is_a;

$container = (new DiContainerBuilder())
    ->load('/app/config/tagged_foo_service.php')
    ->build();

$foo = $container->get(Foo::class);

// перебор коллекции
foreach ($foo->services as $id => $service) {
    var_dump($id, is_object($service));
}
// вывод в консоли:
//string(16) "App\Services\Bar"
// bool(true)
//string(16) "App\Services\Baz"
// bool(true)


// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has(Bar::class),
    $foo->services->get(Bar::class) instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services[Baz::class]),
    $foo->services[Baz::class] instanceof Baz,
);
// bool(true)
// bool(true)

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
// int(2)
```

#### Конфигурация через атрибуты { #example-inject-lazy-collection-with-key-assess-attributes }

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md),
к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

PHP классы:

::: code-group

```php [Foo.php]
//file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.access_by_key')]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.access_by_key')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.access_by_key')]// [!code ++]
final class Baz
{
    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};
use function is_a;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

// перебор коллекции
foreach ($foo->services as $id => $service) {
    var_dump($id, is_object($service));
}
// вывод в консоли:
//string(16) "App\Services\Bar"
// bool(true)
//string(16) "App\Services\Baz"
// bool(true)


// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has(Bar::class),
    $foo->services->get(Bar::class) instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services[Baz::class]),
    $foo->services[Baz::class] instanceof Baz,
);
// bool(true)
// bool(true)

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
// int(2)
```

## Ключ в коллекции как целое число { #key-as-int }

Внедряемая коллекция может быть представлен простым списком, где ключи элементов представлены целыми числами от `0` до `n`.

В [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) указать значение `false` в параметре `$useKeys`:

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                'tags.access_by_key',
                useKeys: false,// [!code ++]
            )
        );

    // ...
};
```

Указать значение `false` в параметре [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs::$useKeys`](../attribute-definition/tagged-as.md):

```php
//file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Foo
{
    public function __construct(
        #[TaggedAs(
            'tags.access_by_key',
            useKeys: false,// [!code ++]
        )]
        public readonly iterable $services
    ) {}    
}
```

## Ключ элемента коллекции в метаданных { #key-in-metadata }

## «Метод-ключа» { #key-in-method }

## «Метод-ключа» по умолчанию { #key-in-method-default }

## Совпадение ключей элементов в коллекции { #keys-equals }


------------

🚧🚧🚧🚧🚧🚧🚧🚧



Пример объявления ключа через метаданные тега:
```php
// src/Classes/DoWrite.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag

// Ключ этого тега 'write' из $options['key_as']
#[Tag('tags.tag_one', options: ['key_as' => 'write'])]
class DoWrite {}
```
```php
// src/Classes/DoRead.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag

// Ключ этого тега 'read' из $options['key_as']
#[Tag('tags.tag_one', options: ['key_as' => 'read'])]
class DoRead {}
```
```php
// src/Services/TaggedServices.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

class TaggedServices {

    public function __construct(
        #[TaggedAs('tags.tag_one', key: 'key_as')]
        private iterable $items
    ) {}
    
    // Ключ в коллекции 'write' или 'read' вызовет соответствующий
    // элемент коллекции
    public function doIt(string $name) {
        // в стиле ContainerInterface
        $class = $this->items->get($name);
        // в стиле php массива
        $class = $this->items[$name];
    }

}
```
📝 [пример реализует получение ключа из метаданных тега](#ключ-в-коллекции-из-метаданных-тега-как-непустая-строка)

> [!TIP]
> В стиле php массивов так же можно использовать
> функции `isset`, `count`.

> [!TIP]
> В стиле `ContainerInterface`
> доступны методы `has` и `get`


### Ключ в коллекции из метаданных тега как непустая строка.
При определении тега к нему можно добавить дополнительные данные (_метаданные_)
через аргумент `$options`.
Для замены ключа по умолчанию на другое строковое значение
необходимо указать в аргументе `$key` имя ключа из метаданных тега.

🐘 Для хелпер функции `diTaggedAs`:
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

class ServiceOne {}
```
```php
// src/Classes/ServiceTwo.php
namespace App\Classes;

class ServiceTwo {}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

class ClassTaggedAs {

    public function __construct(
        private iterable $items
    ) {}
    
    public function doFoo() {
        $this->items->get('foo'); // в стиле ContainerInterface
    }
    
    public function doBaz() {
        $this->items['baz']; // в стиле php массива
    }
}
```
```php
// config/services.php
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function ():\Generator {

   yield  diAutowire(App\Classes\ServiceOne::class)
        ->bindTag('tags.tag_one', options: ['key_as' => 'foo']);

    yield diAutowire(App\Classes\ServiceTwo::class)
        ->bindTag('tags.tag_one', options: ['key_as' => 'baz']);

    yield diAutowire(App\Classes\ClassTaggedAs::class)
        ->bindArguments(
            diTaggedAs('tags.tag_one', key: 'key_as') // ключ будет получен из метаданных тега
        );
};
```
#️⃣ Для php атрибута `#[TaggedAs]`:
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'])]
class ServiceOne {}
```
```php
// src/Classes/ServiceTwo.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'baz'])]
class ServiceTwo {}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassTaggedAs {

    public function __construct(
        // ключ будет получен из метаданных тега - $options['key_as']
        #[TaggedAs('tags.tag_one', key: 'key_as')]
        private iterable $items
    ) {}
    
    public function doFoo() {
        $this->items->get('foo'); // в стиле ContainerInterface
    }
    
    public function doBaz() {
        $this->items['baz']; // в стиле php массива
    }
}
```
⚖ Пример поведения коллекции если значения ключей совпадают:
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'])]
class ServiceOne {}
```
```php
// src/Classes/ServiceTwo.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'], priority: 100)]
class ServiceTwo {}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassTaggedAs {

    public function __construct(
        // ключ будет получен из метаданных тега - $options['key_as']
        #[TaggedAs('tags.tag_one', key: 'key_as')]
        private iterable $items
    ) {}
}
```
> [!NOTE]
> Так как оба тегированных класса содержат в метаданных (в `$options`) одинаковые значения `'key_as' => 'foo'`,
> то при получении коллекции в свойство `ClassTaggedAs::$items` по тегу будет учтён приоритет определения (`priority`). У класса `ServiceTwo`
> приоритет `100`, а у класса `ServiceOne` приоритет не определен – таким образом при одинаковых ключах у определений
> в коллекцию попадёт только `ServiceTwo` у которого приоритет `100`.

### Ключ из метаданных тега через метод класса.
Если необходимо получать ключ определения в коллекцию более сложным образом, то для php класса можно определить метод
через который будет получено значение ключа.
Для объявления получения ключа коллекции через метод класса
необходимо чтобы строка в значении
начиалась с `self::` и после двоеточия указывается имя метода.

Пример ключа из метода:
`['some_key' =>'self::methodName']`.

> [!IMPORTANT]
> Метод реализующий получение ключа должен быть объявлен как `public static function` и возвращать тип `string`.
> В качестве аргументов метод принимает два параметра:
> - `string $tag` – имя тега;
> - `array $options` – метаданные тега;

Пример объявления и реализации метода у класса для получения ключа коллекции:
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'self::getKey'])] // 🚩 ключ коллекции из метода класса
#[Tag('tags.simplest', options: ['key_as' => 'self::getKey'])]
class ServiceOne {

    public static function getKey(string $tag): string {
        return match($tag) {
            'tags.tag_one' => 'qux',
            default => 'bar'
        }
    }

}
```
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'])]
class ServiceTwo {}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassTaggedAs {

    public function __construct(
        // ключ будет получен из метаданных тега $options['key_as']
        #[TaggedAs('tags.tag_one', key: 'key_as')]
        private iterable $items
    ) {}

}
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Rules\ClassTaggedAs::class` в свойстве `ClassTaggedAs::$items`
> содержится «ленивая» коллекция из классов `ServiceOne` будет иметь ключ `qux` полученный из метода `ServiceOne::getKey()`,
> `ServiceTwo` будет иметь ключ `foo` полученный их метаданных тега (_значения в `$options`_).

### Ключ из метода класса по умолчанию.
Так же предусмотрено объявление метода для php класса,
через который будет произведена попытка получить ключ для коллекции
если у тегированного определения не указан ключ для коллекции в метаданных (_в `$options`_).

Указать метод получения ключа по умолчанию можно через аргумент
`$keyDefaultMethod` [в хелпер функции diTaggedAs](../03-php-definition.md#ditaggedas)
или у [php атрибута #[TaggedAs]](../02-attribute-definition.md#taggedas).

> [!IMPORTANT]
> Метод реализующий получение ключа должен быть объявлен как `public static function` и возвращать тип `string`.
> В качестве аргументов метод принимает два параметра:
> - `string $tag` – имя тега;
> - `array $options` – метаданные тега;

Пример получения ключа коллекции по интерфейсу:
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

class ServiceOne implements ServiceInterface {

    public static function getServiceKey(): string {
        return 'bar';
    }

}
```
```php
// src/Classes/ServiceTwo.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

class ServiceTwo implements ServiceInterface {

    public static function getServiceKey(): string {
        return 'foo';
    }

}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassTaggedAs {

    public function __construct(
        #[TaggedAs(ServiceInterface::class, keyDefaultMethod: 'getServiceKey')]
        private iterable $items
    ) {}

}
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Rules\ClassTaggedAs::class` в свойстве `ClassTaggedAs::$items`
> содержится итерируемая «ленивая» коллекция из
> классов `ServiceOne` с ключём `bar` полученный из метода `ServiceOne::getServiceKey()`,
> `ServiceTwo` с ключём `foo` полученный из метода `ServiceTwo::getServiceKey()`.

<!--@include: ../_include/term_notes.md-->
<!--@include: ../php-definition/_include/bind-tag_notes.md-->
