# 🔖 Работа с тегами в контейнере
Теги позволяют расширить возможности работы с зарегистрированными сервисами,
собирая сервисы в коллекции (_списки_).
Результат выполнения может быть применен для параметров с типом:
- `iterable`
    - `\Traversable`
        - `\Iterator`
- `\ArrayAccess`
- `\Psr\Container\ContainerInterface`
- `array` требуется использовать параметр `$isLazy = false`.
- Составной тип (_intersection types_) для ленивых коллекций (`$isLazy = true`)
    - `\ArrayAccess&\Iterator&\Psr\Container\ContainerInterface`.

Любое определение в контейнере может быть отмечено
одним или несколькими тегами.
Каждый тег может содержать мета-данные переданные в виде массива.

Тегирование сервисов можно произвести при объявлении в стиле [php определений](01-php-definition.md)
или используя [PHP атрибуты](02-attribute-definition.md).

> [!IMPORTANT]
> #️⃣ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере. Если сервис не зарегистрирован напрямую в контейнере
> используйте [импорт классов из директорий проекта через `DiContainerBuilder::import()`](06-container-builder.md).

Для получения тегированных сервисов для параметров определений (_параметры – конструктора, метода или функции_) нужно использовать:
- `diTaggedAs` – [хэлпер функцию](01-php-definition.md#ditaggedas) в стиле php определений 
- `#[TaggedAs]` – [php атрибут](02-attribute-definition.md#taggedas) 

### Ленивая коллекция
Особенности получения коллекции в том что по умолчанию
она будет получена как "ленивая" – инициализация тегированного сервиса происходит
только когда к нему произойдёт обращение.

### Ключ элемента в коллекции.
По умолчанию в качестве ключей элементов в коллекции используются идентификаторы
определений в контейнере (_container identifier – не пустая строка_). Это поведение можно изменить
через аргументы `$useKeys`, `$key`, `$keyDefaultMethod` [в хэлпер функции diTaggedAs](01-php-definition.md#ditaggedas)
или у [php атрибута #[TaggedAs]](02-attribute-definition.md#taggedas) чтобы ключи элементов в коллекции были отличными
от идентификаторов определений (_container identifier_) представленные не пустыми строками
или целыми числами (_последовательные значения от нуля и больше_).

Больше информации [об использовании ключей в коллекции.](#использование-ключей-в-коллекции)

## 🐘 Объявление тега в стиле php определений.

Для указания тегов используется метод `bindTag`
который доступен через [хэлпер функции](01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8)
реализующие интерфейс `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`

```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```

Аргументы:
- `$name` – имя тега
- `$options` – метаданные для тега
- `$priority` – [приоритет в коллекции](#приоритет-в-коллекции) тегов

> [!NOTE]
> 🤝 Соглашение по именованию тегов и ключей массива у аргумента `$options`.
> - использовать строчные буквы
> - разделять символом подчеркивание "_" части имени если определение состоит из нескольких слов.
> - разделять символом точка "." группы в определении

Пример использования имён по соглашению:
```php
use function Kaspi\DiContainer\diAutowire;
// в стиле php определений
diAutowire(SomeClass::class)
    ->bindTag(
        'tags.any_service',
         options: [
            'some_info.data' => 'foo',
            'some_info.current_value' => 'baz',
        ]
     );
```
```php
use Kaspi\DiContainer\Attributes\Tag;
// через php атрибуты 
#[Tag(
    'tags.any_service',
    options: [
        'some_info.data' => 'foo',
        'some_info.current_value' => 'baz',
    ]
)]
final class SomeClass {}
```

> [!TIP]
> 🔔 Аргумент `$options` может содержать дополнительные метаданные
для устанавливаемого тега представленные массивом.
Ключ массива `$options` это непустая строка, а значение это простой php тип (_`string`, `int`, `bool`, `null` или `array` из этих типов_).

> [!WARNING]
> Для аргумента `$options` зарезервирован ключ массива `priority.method` – значение типа `string`.
> ```php
> ['priority.method' => 'someValue']
> ```
> Значение это метод класса возвращающий приоритет (_priority_) для тега если не определен `priority`.
> Метод должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В приведенном выше примере метод "someValue" принимает два аргумента:
>  - `string $tag` – имя тега;
>  - `array $options` – метаданные тега;
>
> Подробнее о [приоритете в коллекции](#приоритет-в-коллекции).

🧪 Пример использования с хэлпер функцией `diAutowire`:
```php
// src/Classes/One.php
namespace App\Classes;

class One {}
```
```php
// src/Classes/Two.php
namespace App\Classes;

class Two {}
```
```php
// src/Services/TaggedServices.php
namespace App\Services;

class TaggedServices {

    public function __construct(private iterable $services) {}

}
```
```php
// config/services.php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator {

    yield diAutowire(App\Classes\One::class)
        ->bindTag(name: 'tags.service_any');

    yield diAutowire(App\Classes\Two::class)
        ->bindTag(name: 'tags.service_any');

    yield diAutowire(App\Services\TaggedServices::class)
        ->bindArguments(services: diTaggedAs('tags.service_any'));

};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$class = $container->get(App\Services\TaggedServices::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Services\TaggedServices::class`
> в свойстве `App\Services\TaggedServices::$services` содержится итерируемая «ленивая» коллекция
> из классов `App\Classes\One`, `App\Classes\Two`.

> [!TIP]
> Если тип аргумента куда добавляется тегированная коллекция `array`
> то необходимо указать "не ленивое" получение:
> ```php
>   use function Kaspi\DiContainer\diTaggedAs;
> 
>   diTaggedAs(tag: 'tags.service_any', isLazy: false)
> ```

#### Получение тегированных сервисов можно применять так же **параметрам переменной длинны**:

> [!WARNING]
> Параметр переменной длины является опциональным и если у него не задан
> аргумент указывающий как разрешать зависимость, то параметр будет пропущен.

```php
// src/Services/TaggedServices.php
namespace App\Services;

class TaggedServices {

    public function __construct(
        array ...$srvGroup
    ) {}

}
```
```php
// config/services.php
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator { 

    yield diAutowire(namespace App\Classes\One::class)
        ->bindTag(name: 'tags.group_1');

    yield diAutowire(App\Classes\Two::class)
        ->bindTag(name: 'tags.group_1');

    yield diAutowire(App\Classes\Three::class)
        ->bindTag(name: 'tags.group_2');

    yield diAutowire(App\Classes\Four::class)
        ->bindTag(name: 'tags.group_2');

    yield diAutowire(App\Services\TaggedServices::class)
        ->bindArguments(
            // аргумент имеет тип array то $isLazy=false
            diTaggedAs('tags.group_1', false),
            diTaggedAs('tags.group_2', false),
        );

};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$class = $container->get(App\Services\TaggedServices::class);
```
> [!NOTE]
> При разрешении параметров конструктора класса `App\Services\TaggedServices::class`
> в свойстве `App\Services\TaggedServices::$srvGroup[0]` содержится массив из классов `App\Classes\One`, `App\Classes\Two`,
> а в свойстве `App\Services\TaggedServices::$srvGroup[1]` массив из классов `App\Classes\Three`, `App\Classes\Four`.

> [!TIP]
> Для использования [именованных аргументов](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)
> и [параметров переменной длины](https://www.php.net/manual/ru/functions.arguments.php#functions.variable-arg-list)
> действуют правила описанные в документации php.

Передать именованные аргументы для сервиса к параметру переменной длины:
```php
use function Kaspi\DiContainer\diTaggedAs;

return static function (): \Generator
    //...
    yield diAutowire(App\Services\TaggedServices::class)
        ->bindArguments(
            // аргумент имеет тип array то $isLazy=false
            srvGroup: diTaggedAs('tags.group_1', false),
            srvGroup_2: diTaggedAs('tags.group_2', false),
        );
};
```
> [!NOTE]
> При разрешении параметров конструктора класса `App\Services\TaggedServices::class`
> в `App\Services\TaggedServices::$srvGroup` содержится массив
> со строковыми ключами `srvGroup` и `srvGroup_2` – как переданные именование аргументы.


## #️⃣ Объявление тега через php атрибут.
Для указания тегов для класса необходимо использовать php атрибут `#[Tag]` ([описание атрибута](02-attribute-definition.md#tag)):

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
Для получения коллекции тегированных сервисов использовать php атрибут `#[TaggedAs]` ([описание атрибута](02-attribute-definition.md#taggedas)):
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
> используйте [импорт классов из директорий проекта через `DiContainerBuilder::import()`](06-container-builder.md).

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

## Interface как имя тега.
В качестве имени тега можно использовать имя интерфейса (**FQCN – Fully Qualified Class Name**)
реализуемого классами. Чтобы такой подход сработал необходимо
чтобы класс реализующий запрашиваемый интерфейс был объявлен
в контейнере.

> [!IMPORTANT]
> #️⃣ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере. Если сервис не зарегистрирован напрямую в контейнере
> используйте [импорт классов из директорий проекта через `DiContainerBuilder::import()`](06-container-builder.md).

### 🐘 Использование в стиле php определений

```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

class RuleB implements RuleInterface {}
```
```php
// src/Rules/RuleC.php
namespace App\Rules;

class RuleC {}
```

```php
// src/Services/SrvRules.php
namespace App\Services;

class SrvRules {

    public function __construct(
        private iterable $rules
    ) {}

    public function validate(mixed $input): mixed {
        foreach ($this->rules as $rule) {
            $input = $rule->validate($input);
        }
        
        return $input;
    }
}
```
```php
// config/services.php
use App\Rules\RuleInterface;
use App\Services\SrvRules;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator {
    yield diAutowire(SrvRules::class)
        ->bindArguments(
            // собрать объявленные классы реализующие интерфейс.
            rules: diTaggedAs(RuleInterface::class)
        );
};

```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$class = $container->get(SrvRules::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `SrvRules::class`
> в свойстве `SrvRules::$rules` содержится итерируемая «ленивая» коллекция
> из классов `RuleA`, `RuleB` – так как они имплементируют `RuleInterface`.
> 
> При таком вызове порядок элементов коллекции
> сервисов не определен и может быть любым.

> [!TIP] 
> Если нужен определенный порядок можно воспользоваться
> аргументом `$priorityDefaultMethod` у хэлпер функции:
> ```php
>   use function Kaspi\DiContainer\diTaggedAs;
> 
>   diTaggedAs(RuleInterface::class, priorityDefaultMethod: 'methodPriority')
> ```
> 🗨 подробнее в разделе [приоритет сервисов в коллекции](#prioritymethod-и-prioritydefaultmethod-для-приоритизации-в-коллекции).

### #️⃣ Использование через php атрибуты

```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

class RuleB implements RuleInterface {}
```
```php
// src/Rules/RuleC.php
namespace App\Rules;

class RuleC {}
```

```php
// src/Services/SrvRules.php
namespace App\Services;

use App\Rules\RuleInterface;
use Kaspi\DiContainer\Attributes\TaggedAs;

class SrvRules {

    public function __construct(
        #[TaggedAs(RuleInterface::class)]
        private iterable $rules
    ) {}

    public function validate(mixed $input): mixed {
        foreach ($this->rules as $rule) {
            $input = $rule->validate($input);
        }
        
        return $input;
    }
}
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

$class = $container->get(App\Services\SrvRules::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `SrvRules::class` 
> в свойстве `SrvRules::$rules` содержится итерируемая «ленивая» коллекция
> из классов `RuleA`, `RuleB` – так как они имплементируют `RuleInterface`.
> 
> При таком вызове порядок элементов коллекции
> сервисов не определен и может быть любым.

> [!TIP]
> Если нужен определенный порядок можно воспользоваться
> аргументом `priorityDefaultMethod` у php атрибута:
> ```php
> #[TaggedAs(RuleInterface::class, priorityDefaultMethod: 'methodPriority')]
> ```
> 🗨 подробнее в разделе [приоритет сервисов в коллекции](#prioritymethod-и-prioritydefaultmethod-для-приоритизации-в-коллекции).

## Приоритет в коллекции.
Приоритет определяет как будут отсортированы сервисы в получаемой коллекции.
Значение приоритета может быть типам `int`, `string`, `null`. 

**Чем больше значение приоритета, тем выше сервис будет расположен в коллекции.**

Сравнение может быть как целых чисел, так и строк.
Сравнение строк происходит как последовательности байтов строк.

Порядок получения приоритета у тегированного элемента коллекции в порядке возрастания значимости:
1. Значение `priority` отличное от `null`.
2. Если элемент является php классом и присутствует `priorityMethod`
то будет выполнена попытка получить значение `priority`
через вызов указанного метода.
3. Если при получении коллекции через `diTaggedAs` или через php атрибут `#[TaggedAs]`
указан параметр `priorityDefaultMethod` и получаемый элемент является php классом
то будет выполнена попытка получить значение `priority` через вызов
метода указанного в `priorityDefaultMethod`.
4. если не нашлось подходящих методов получения то `priority` будет `null`

> [!IMPORTANT]
> Метод указанный в `priorityMethod` и `priorityDefaultMethod`
> должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два параметра:
>  - `string $tag` – имя тега;
>  - `array $options` – метаданные тега;
> 
> Эти параметры метода можно использовать построения логики выдачи `priority`.

### Опция `priority`  для приоритизации в коллекции.

#### 🐘 В стиле php определений

Использовать аргумент `$priority` у [метода `bindTag`](#-объявление-тега-в-стиле-php-определений) как указание приоритета:

```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

class RuleB implements RuleInterface {}
```
```php
// src/Rules/RuleC.php
namespace App\Rules;

class RuleC implements RuleInterface {}
```
```php
// src/Rules/Rules.php
namespace App\Rules;

class Rules {

    public function __construct(private iterable $rules) {}

}
```

```php
// config/services.php
use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): \Generator {

   yield diAutowire(App\Rules\RuleA::class)
        ->bindTag(name: 'tags.rules', priority: 10);
   
   yield diAutowire(App\Rules\RuleB::class)
        ->bindTag(name: 'tags.rules');

   yield diAutowire(App\Rules\RuleC::class)
        ->bindTag(name: 'tags.rules', priority: 100);

    yield diAutowire(App\Rules\Rules::class)
        ->bindArguments(rules: diTaggedAs('tags.rules'));
     
};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$class = $container->get(App\Rules\Rules::class);
```

> [!NOTE]
> При разрешении аргументов конструктора класса `App\Rules\Rules::class`
> в свойстве `Rules::$rules` содержится итерируемая «ленивая» коллекция
> отсортированные по приоритету:
> 1. `RuleC` – `priority === 100`
> 2. `RuleA` – `priority === 10`
> 3. `RuleB` – `priority === null`

#### #️⃣ Через php атрибуты

Использовать аргумент `$priority` у php атрибута `#[Tag]`
как указание приоритета:
```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules', priority: 10)]
class RuleA implements RuleInterface {}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules')]
class RuleB implements RuleInterface {}
```
```php
// src/Rules/RuleC.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules', priority: 100)]
class RuleC {} implements RuleInterface {}
```
```php
// src/Rules/Rules.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\TaggedAs;

class Rules {

    public function __construct(
        #[TaggedAs('tags.rules')]
        private iterable $rules
    ) {}

}
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\Rules\\', src: __DIR__.'/src/Rules/')
    ->build()
;

$container->get(App\Rules\Rules::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Rules\Rules::class`
> в свойстве `Rules::$rules` содержится итерируемая «ленивая» коллекция
> отсортированные по приоритету:
> 1. `RuleC` – `priority === 100`
> 2. `RuleA` – `priority === 10`
> 3. `RuleB` – `priority === null`

### `priorityMethod` и `priorityDefaultMethod` для приоритизации в коллекции.
Указать приоритет тега в коллекции `priority` можно альтернативным способами если определение в контейнере является php-классом:

- `priorityMethod` – метод возвращающий `priority` у тегированного php класса указанный при объявлении тега;
- `priorityDefaultMethod` – метод указанный через
[хэлпер функцию `diTaggedAs`](01-php-definition.md#ditaggedas)
или через [php атрибут #[TaggedAs]](02-attribute-definition.md#taggedas)
который **может быть реализован** в тегированном php классе возвращающий `priority`.
 
#### 🐘 В стиле php определений

Использование метаданных в аргументе `$options` – указав в массиве ключ `priority.method` [у метода `bindTag`](#-объявление-тега-в-стиле-php-определений)
как указание приоритета:
```php
['priority.method' => 'methodName']
```
Пример использования значения `priority.method`:
```php
// src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {
    public static function getPriority(): int;
}
```
```php
// src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {

    public static function getPriority(): int {
        return 10;
    }

}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

class RuleB implements RuleInterface {

    public static function getPriority(): int {
        return 0;
    }

}
```
```php
// src/Rules/RuleC.php
namespace App\Rules;

class RuleC {

    public static function getPriorityForCollection(string $tag): string|int|null {
        return match ($tag) {
            'tags.rules' => 100,
            'tags.other-name' => 'GROUP100:0001',
            default => null,
        };
    }

}
```
```php
// src/Rules/Rules.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\TaggedAs;

class Rules {

    public function __construct(
        private iterable $rules
    ) {}

}
```
```php
// config/services.php
use function \Kaspi\DiContainer\diAutowire;
use function \Kaspi\DiContainer\diTaggedAs;

return static function (): \Generator {
    
   yield diAutowire(App\Rules\RuleA::class) // реализует метод интерфейса RuleInterface::getPriority
        ->bindTag(
            name: 'tags.rules',
            options: ['priority.method' => 'getPriority']
        );

   yield diAutowire(App\Rules\RuleB::class) // реализует метод интерфейса RuleInterface::getPriority
        ->bindTag(
            name: 'tags.rules',
            options: ['priority.method' => 'getPriority']
        );

   yield diAutowire(App\Rules\RuleC::class)
        ->bindTag(name: 'tags.rules'); // не указываем явно данные как получать `priority`
                                       // метод по умолчанию будет указан в `diTaggedAs`.

    yield diAutowire(App\Rules\Rules::class)
        ->bindArguments(
            rules: diTaggedAs(
                'tags.rules',
                // если нет `priority` и `priority.method`
                // попробовать вызвать метод - className::getPriorityForCollection() 
                priorityDefaultMethod: 'getPriorityForCollection'
            )
        );
};
```
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(__DIR__.'/config/services.php')
    ->build()
;

$container->get(App\Rules\Rules::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Rules\Rules::class`
> в свойстве `Rules::$rules` содержится итерируемая «ленивая» коллекция
> отсортированные по приоритету:
> 1. `RuleC` – `RuleC::getPriorityForCollection() === 100`
> 2. `RuleA` – `RuleA::getPriority() === 10`
> 3. `RuleB` – `RuleB::getPriority() === 0`

### #️⃣ Через php атрибуты

Использование аргумент `$priorityMethod` у php атрибута `#[Tag]`
как указание приоритета:
```php
// src/Rules/RuleA.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules', priorityMethod: 'getPriority')]
class RuleA {

    public static function getPriority(): int {
        return 10;
    }

}
```
```php
// src/Rules/RuleB.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.rules', priorityMethod: 'getPriority')]
class RuleB {

    public static function getPriority(): int {
        return 0;
    }

}
```
```php
// src/Rules/RuleC.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;

// 🚩 без явного указания priority и priorityMethod
// приоритет может быть получен через priorityDefaultMethod
#[Tag(name: 'tags.rules')]
class RuleC {

    public static function getPriorityForCollection(string $tag): string|int|null {
        return match ($tag) {
            'tags.rules' => 100,
            'tags.other-name' => 'GROUP100:0001',
            default => null,
        };
    }

}
```
```php
// src/Rules/Rules.php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\TaggedAs;

class Rules {

    public function __construct(
        #[TaggedAs(
            'tags.rules',
            // если не объявлен `priority` и `priorityMethod`
            // то попытаться вызвать метод `getPriorityForCollection` у тегированного класса
            priorityDefaultMethod: 'getPriorityForCollection'
        )]
        private iterable $rules
    ) {}

}
```
```php
use App\Rules\Rules;
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\Rules\\', src: __DIR__.'/src/Rules/')
    ->build()
;

$container->get(Rules::class);
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Rules\Rules::class`
> в свойстве `Rules::$rules` содержится итерируемая «ленивая» коллекция
> отсортированные по приоритету:
> 1. `RuleC` – `RuleC::getPriorityForCollection() === 100`
> 2. `RuleA` – `RuleA::getPriority() === 10`
> 3. `RuleB` – `RuleB::getPriority() === 0`

## Использование ключей в коллекции.

По умолчанию в качестве ключей элементов в коллекции используются идентификаторы
определений в контейнере (_container identifier – не пустая строка_).
Это поведение можно изменить:
- указав в метаданных ключ (_аргумент `$options` у тега_);
- указа конкретный метод у тегированного класса (_аргумент `$options` у тега_);
- указав метод по умолчанию в хэлпер функции `diTaggedAs` или у атрибута `#[TaggedAs]`
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

Для хэлпер функции `diTaggedAs`:

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

🐘 Для хэлпер функции `diTaggedAs`:
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
`$keyDefaultMethod` [в хэлпер функции diTaggedAs](01-php-definition.md#ditaggedas)
или у [php атрибута #[TaggedAs]](02-attribute-definition.md#taggedas).

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

## Исключение определений из коллекции.
Коллекции могу исключать собираемые тегированные
определения.

### Исключение вызывающего класса.
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
