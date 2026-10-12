# 🚫 Исключение элементов из коллекции { #title }

## Обзор { #overview }

Коллекции могу исключать собираемые элементы. Исключить из коллекции можно вызывающий PHP класс т.е. самого себя или по идентификатору контейнера элемента коллекции.

## Исключение вызывающего класса { #exclide-self }

В коллекцию элементов может попадать PHP класс внедряющий эту же коллекцию. Для исключения такого поведения обратите внимание на параметр `$selfExclude`
в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) и [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

🚧🚧🚧🚧🚧

При сборе коллекции по тегу исключается php вызывающий коллекцию,
даже если он отмечен соответствующим тегом.
```php
// src/Classes/One.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.aaa')]
class One {}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\{Tag, TaggedAs}; 

#[Tag('tags.aaa')]
class ClassTaggedAs {

    public function __construct(
        #[TaggedAs('tags.aaa')]
        public iterable $items
    ) {}

}
```
> [!NOTE]
> При разрешении аргументов конструктора класса `ClassTaggedAs::class` в коллекции
> `ClassTaggedAs::$items` будет отсутствовать класс `ClassTaggedAs`
> даже если он отмечен запрашиваемым тегом `tags.aaa`.

При необходимости изменить это поведение нужно указать аргумент
`$selfExclude = false` чтобы вызывающий класс также попал в коллекцию.

#️⃣ В стиле php атрибута:
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs; 
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

// php атрибут
public function __construct(
        #[TaggedAs('tags.aaa', selfExclude: false)]
        public iterable $items
) {}
```
🐘 в стиле php определений:
```php
// config/services.php
return static function ():\Generator {

    yield diAutowire(ClassTaggedAs::class)
        ->bindTag('tags.aaa')
        ->bindArguments(
            items: diTaggedAs('tags.aaa', selfExclude: false)
        )

};
```
### Исключение определений по идентификатору контейнера.
Если по какой-то причине необходимо исключить некоторые определения
из тегированной коллекции, воспользуйтесь
аргументом `$containerIdExclude` который содержит массив
идентификаторов контейнера (_container identifiers_).

#️⃣ Исключение определений при объявлении через php атрибуты:
```php
// src/Classes/One.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag; 

#[Tag(name: 'tags.aaa')]
class One {}
```
```php
// src/Classes/Two.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag; 

#[Tag(name: 'tags.aaa')]
class Two {}
```
```php
// src/Classes/Three.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag; 

#[Tag(name: 'tags.aaa')]
class Three {}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs; 

class ClassTaggedAs {

    public function __construct(
        // исключить из коллекции идентификатор контейнера 
        #[TaggedAs('tags.aaa', containerIdExclude: [App\Two::class])]
        public iterable $items
    ) {}

}
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Classes\ClassTaggedAs::class` в коллекции
> `App\Classes\ClassTaggedAs::$items` будет отсутствовать класс `App\Classes\Two`
> даже если он отмечен запрашиваемым тегом `tags.aaa`.

🐘 Исключение определений при объявлении в стиле php:
```php
// src/Services/EmailNotify.php
namespace App\Services;

class EmailNotify {

    public function __construct(private array $emails) {}

}
```
```php
// config/services.php
use function Kaspi\DiContainer\{diValue, diAutowire, diTaggedAs};

return [
    'emails.admin' => diValue('admin@site.com')
        ->bindTag('tags.site_email'),

    'emails.order' => diValue('order@site.com')
        ->bindTag('tags.site_email'),

    'emails.manager' => diValue('manager@site.com')
        ->bindTag('tags.site_email'),

    // ...
    
    diAutowire(App\Services\EmailNotify::class)
        ->bindArguments(
            emails: diTaggedAs(
                'tags.site_email',
                isLazy: false,
                useKeys: false,
                containerIdExclude: ['emails.order'] // исключить идентификатор контейнера
            )
        )   
];
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Services\EmailNotify::class` в массиве
> `EmailNotify::$emails` будет отсутствовать определение имеющее
> идентификатор контейнера `emails.order` несмотря на то что
> отмечено тегом `tags.site_email`, таким образом в `EmailNotify::$emails`
> появится значение `['admin@site.com', 'manager@site.com']`.
