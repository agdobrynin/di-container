# 🔑 Использование ключей коллекции { #title }

## Обзор { #overview }

По умолчанию в качестве ключей элементов в коллекции используются идентификаторы
определений в контейнере (_container identifier – не пустая строка_).
Это поведение можно изменить:
- указав в метаданных ключ (_аргумент `$options` у тега_);
- указа конкретный метод у тегированного класса (_аргумент `$options` у тега_);
- указав метод по умолчанию в хелпер функции `diTaggedAs` или у атрибута `#[TaggedAs]`
  который будет вызван у тегированного класса для получения ключа;

> [!WARNING]
> Если в коллекции тегированных определений встречаются одинаковые
> ключи, то в коллекцию попадёт определение с более высоким приоритетом (`priority`),
> остальные определения с таким же значением ключа будут игнорированы.

"Ленивая" (`$isLazy = true`) коллекция реализует следующие интерфейсы:

- `\Iterator`
- `\Psr\Container\ContainerInterface`
- `\ArrayAccess`
- `\Countable`

что даёт возможность доступа к элементам коллекции
по именам ключей в стиле php массивов или в стиле `ContainerInterface`

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

> [!IMPORTANT]
> Если сервис не найден по запрошенному ключу
> будет выброшено исключение реализующее
> интерфейс `Psr\Container\NotFoundExceptionInterface`.

### Ключ в коллекции как целое число.
Для получения в качестве ключей коллекции целых чисел (_последовательные значения от нуля и больше_)
нужно указать в аргументе `$useKeys=false`.

Для хелпер функции `diTaggedAs`:

```php
// config/services.php
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator {

    yield diAutowire(App\Classes\ClassTaggedAs::class)
        ->bindArguments(
            diTaggedAs('tags.tag_one', useKeys: false) // ключи целые числа от 0 до n
        );

};
```
Для php атрибута `#[TaggedAs]`:
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassTaggedAs {

    public function __construct(
        #[TaggedAs('tags.tag_one', useKeys: false)] // ключи целые числа
        private iterable $items
    ) {}

}
```
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
