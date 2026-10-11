# #️⃣  Конфигурирование тегов через php атрибут { #title }

## Обзор { #overview }

Для указания тегов для класса необходимо использовать php атрибут `#[Tag]` ([описание атрибута](../02-attribute-definition.md#tag)):

```php
// src/Any/One.php
namespace App\Any;

use Kaspi\DiContainer\Attributes\Tag; 

#[Tag(name: 'tags.services.group_one')]
#[Tag(name: 'tags.services.group_two', priority: 5)]
class One {}
```
```php
// src/Any/Two.php
namespace App\Any;

use Kaspi\DiContainer\Attributes\Tag; 

#[Tag('tags.services.group_two', priority: 10)]
class Two {}
```
Для получения коллекции тегированных сервисов использовать php атрибут `#[TaggedAs]` ([описание атрибута](../02-attribute-definition.md#taggedas)):
```php
// src/Services/TaggedServices.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

class TaggedServices {

    public function __construct(
        #[TaggedAs('tags.services.group_two')]
        private iterable $services
    ) {}

}
```
> [!IMPORTANT]
> #️⃣ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере. Если сервис не зарегистрирован напрямую в контейнере
> используйте [импорт классов из директорий проекта через `DiContainerBuilder::import()`](../00-container-builder.md).

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$class = $container->get(App\Services\TaggedServices::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `TaggedServices::class`
> в свойстве `TaggedServices::$services` содержится итерируемая «ленивая» коллекция
> из классов `Two`, `One` (_такой порядок обусловлен значением 'priority' у тегов_).

#### Получение тегированных сервисов можно применять так же **параметрам переменной длины**:

> [!WARNING]
> Параметр переменной длины является опциональным и если у него не задан
> PHP атрибут указывающий какой аргумент использовать
> для разрешения зависимости, то он будет пропущен.

```php
// src/Services/TaggedService.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

class TaggedServices {

    public function __construct(
        // аргумент имеет тип array то $isLazy=false
        #[TaggedAs('tags.group_1', false)]
        #[TaggedAs('tags.group_2', false)]
        array ...$srvGroup
    ) {}

}
```
```php
// src/Classes/One.php
namespace App\Classes;

#[Tag('tags.group_1')]
class One {}
```
```php
// src/Classes/Two.php
namespace App\Classes;

#[Tag('tags.group_1')]
class Two {}
```
```php
// src/Classes/Three.php
namespace App\Classes;

#[Tag('tags.group_2')]
class Three {}
```
```php
// src/Classes/Four.php
namespace App\Classes;

#[Tag('tags.group_2')]
class Four {}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$class = $container->get(TaggedServices::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `TaggedServices::class`
> в свойстве `TaggedServices::$srvGroup[0]` массив из классов `One`, `Two`,
> а в `TaggedServices::$srvGroup[1]` массив из классов `Three`, `Four`.