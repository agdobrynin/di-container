# 🔖 Работа с тегами в контейнере
Теги позволяют расширить возможности работы с зарегистрированными сервисами,
собирая сервисы в коллекции (_списки_) и применяется для параметров с типом `iterable` и `array`.

Любое определение в контейнере может быть отмечено
одним или несколькими тегами.
Каждый тег может содержать мета-данные переданные в виде массива.

Тегирование сервисов можно произвести при объявлении в стиле [php определений](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md)
или используя [PHP атрибуты](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).

> #️⃣ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере через хэлпер функцию `diAutowire`

Для получения тегированных сервисов на аргументы (_параметры - конструктора, метода или аргументы функции_) нужно использовать:
- `diTaggedAs` - [хэлпер функцию](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#ditaggedas) в стиле php определений 
- `#[TaggedAs]` - [php атрибут](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#taggedas) 

### Ленивая коллекция
Особенности получения коллекции в том что по умолчанию
она будет получена как "ленивая" - инициализация тегированного сервиса происходит
только когда к нему произойдёт обращение.

Для "ленивой" коллекции необходимо чтобы тип параметра
куда будет помещена результат был `iterable`.
В случае если тип параметра `array` куда будет помещён результат
то необходимо отметить что коллекция "не ленивая", таким образом
сервисы будут инициализированы и помещены в php массив.

### Ключ элемента в коллекции.
По умолчанию в качестве ключей элементов в коллекции используются идентификаторы
определений в контейнере (_container identifier - не пустая строка_). Это поведение можно изменить
через аргументы `$useKeys`, `$key`, `$keyDefaultMethod` [в хэлпер функции diTaggedAs](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#ditaggedas)
или у [php атрибута #[TaggedAs]](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#taggedas) чтобы ключи элементов в коллекции были отличными
от идентификаторов определений (_container identifier_) представленные не пустыми строками
или целыми числами (_последовательные значения от нуля и больше_).

Больше информации [об использовании ключей в коллекции.](#использование-ключей-в-коллекции)

## 🐘 Объявление тега в стиле php определений.

Для указания тегов используется метод `bindTag`
который доступен через [хэлпер функции](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8)
реализующие интерфейсы:
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface`
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionConfigAutowireInterface`

```php
bindTag(string $name, array $options = [], null|int|string $priority = null)
```

Аргументы:
- `$name` - имя тега
- `$options` - метаданные для тега
- `$priority` - [приоритет в коллекции](#приоритет-в-коллекции) тегов

🤝 Соглашение по именованию тегов и ключей массива у аргумента `$options`.
- использовать строчные буквы
- разделять символом подчеркивание "_" части имени если определение состоит из нескольких слов.
- разделять символом точка "." группы в определении

Например:
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
// через php атрибуты
use Kaspi\DiContainer\Attributes\Tag;
 
#[Tag(
    'tags.any_service',
    options: [
        'some_info.data' => 'foo',
        'some_info.current_value' => 'baz',
    ]
)]
final class SomeClass {}
```

> 🔔 Аргумент `$options` может содержать дополнительные метаданные
для устанавливаемого тега представленные массивом.
Ключ массива `$options` это непустая строка, а значение это простой php тип (_`string`, `int`, `bool`, `null` или `array` из этих типов_).

> ⚠ Для аргумента `$options` зарезервирован ключ массива `priority.method` - значение типа `string`.
> ```php
> ['priority.method' => 'someValue']
> ```
> Значение это метод класса возвращающий приоритет (_priority_) для тега если не определен `priority`.
> Метод должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В приведенном выше примере метод "someValue" принимает два аргумента:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;
>
> Подробнее о [приоритет в коллекции](#приоритет-в-коллекции).

🧪 Пример использования с хэлпер функцией `diAutowire`:
```php
// определение классов
class One {}

class Two {}

class TaggedServices {

    public function __construct(private iterable $services) {}

}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definitions = [
    diAutowire(One::class)
        ->bindTag(name: 'tags.service_any'),

    diAutowire(Two::class)
        ->bindTag(name: 'tags.service_any'),

    diAutowire(TaggedServices::class)
        ->bindArguments(services: diTaggedAs('tags.service_any')),
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(TaggedServices::class);
// теперь в свойстве `TaggedServices::$services` содержится итерируемая коллекция
// из классов One, Two.
```
> ⚠ Если тип аргумента куда добавляется тегированная коллекция `array`
> то необходимо указать "не ленивое" получение:
> ```php
> use function Kaspi\DiContainer\diTaggedAs;
> 
> diTaggedAs(tag: 'tags.service_any', isLazy: false)
> ```
#### Получение тегированных сервисов можно применять так же **параметрам переменной длинны**:
```php
class TaggedServices {

    public function __construct(
        array ...$srvGroup
    ) {}

}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definitions = [
    diAutowire(One::class)
        ->bindTag(name: 'tags.group_1'),

    diAutowire(Two::class)
        ->bindTag(name: 'tags.group_1'),

    diAutowire(Three::class)
        ->bindTag(name: 'tags.group_2'),

    diAutowire(Four::class)
        ->bindTag(name: 'tags.group_2'),

    diAutowire(TaggedServices::class)
        ->bindArguments(
            srvGroup: [
                // аргумент имеет тип array то $isLazy=false
                diTaggedAs('tags.group_1', false),
                diTaggedAs('tags.group_2', false),
            ]
        ),
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(TaggedServices::class);
/**
 * В свойстве TaggedServices::$srvGroup[0] будут классы One, Two.
 * В свойстве TaggedServices::$srvGroup[1] будут классы Three, Four.
 */
```

## #️⃣ Объявление тега через php атрибут.
Для указания тегов для класса необходимо использовать php атрибут `#[Tag]` ([описание атрибута](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#tag)):

```php
use Kaspi\DiContainer\Attributes\Tag; 
namespace App\Any;

#[Tag(name: 'tags.services.group_one')]
#[Tag(name: 'tags.services.group_two', priority: 5)]
class One {}

#[Tag('tags.services.group_two', priority: 10)]
class Two {}
```
Для получения коллекции тегированных сервисов использовать php атрибут `#[TaggedAs]` ([описание атрибута](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#taggedas)):
```php
use Kaspi\DiContainer\Attributes\TaggedAs;

namespace App\Services;

class TaggedServices {

    public function __construct(
        #[TaggedAs('tags.services.group_two')]
        private iterable $services
    ) {}

}
```
> #️⃣ При использовании тегирования через PHP атрибуты
> необходимо чтобы класс использующий `#[Tag]` был зарегистрирован
> в контейнере через хэлпер функцию [diAutowire](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#diautowire)

```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definitions = [

    diAutowire(One::class),

    diAutowire(Two::class),

];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(TaggedServices::class);
// теперь в свойстве `TaggedServices::$services` содержится итерируемая коллекция
// из классов Two, One - такой порядок обусловлен
// значением 'priority' у тегов собранных в коллекцию
```
#### Получение тегированных сервисов можно применять так же **параметрам переменной длинны**:
```php
use Kaspi\DiContainer\Attributes\{Tag, TaggedAs};

class TaggedServices {

    public function __construct(
        // аргумент имеет тип array то $isLazy=false
        #[TaggedAs('tags.group_1', false)]
        #[TaggedAs('tags.group_2', false)]
        array ...$srvGroup
    ) {}

}

#[Tag('tags.group_1')]
class One {}

#[Tag('tags.group_1')]
class Two {}

#[Tag('tags.group_2')]
class Three {}

#[Tag('tags.group_2')]
class Four {}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definitions = [
    // Объявить класс чтобы была возможность работы с атрибутом #[Tag]
    diAutowire(One::class),

    diAutowire(Two::class),

    diAutowire(Three::class),

    diAutowire(Four::class),
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(TaggedServices::class);
/**
 * В свойстве TaggedServices::$srvGroup[0] будут классы One, Two.
 * В свойстве TaggedServices::$srvGroup[1] будут классы Three, Four.
 */
```
## Interface как имя тега.
В качестве имени тега можно использовать имя интерфейса (**FQCN - Fully Qualified Class Name**)
реализуемого классами. Чтобы такой подход сработал необходимо
чтобы класс реализующий запрашиваемый интерфейс был объявлен
через хэлпер функцию [diAutowire](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#diautowire).

### 🐘 Использование в стиле php определений

```php
// Определение классов
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

class RuleC {}
```

```php
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
use Kaspi\DiContainer\DiContainerFactory;
use App\Rules\{RuleA, RuleB, RuleC, RuleInterface};
use App\Services\SrvRules;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

// Объявить классы 
$definitions = [

    diAutowire(RuleA::class),

    diAutowire(RuleB::class),

    diAutowire(RuleC::class),

    diAutowire(SrvRules::class)
        ->bindArguments(
            // собрать объявленные классы реализующие интерфейс.
            rules: diTaggedAs(RuleInterface::class)
        )

];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(SrvRules::class);
// теперь в свойстве `SrvRules::$rules` содержится итерируемая коллекция
// из классов RuleA, RuleB - так как они имплементируют RuleInterface
```
> 📝 При таком вызове порядок элементов коллекции
> сервисов не определен и может быть любым.
> 
> Если нужен определенный порядок можно воспользоваться
> аргументом `$priorityDefaultMethod` у хэлпер функции:
> ```php
> use function Kaspi\DiContainer\diTaggedAs;
> 
> diTaggedAs(RuleInterface::class, priorityDefaultMethod: 'methodPriority')
> ```
> 🗨 подробнее в разделе [приоритет сервисов в коллекции](#prioritymethod-и-prioritydefaultmethod-для-приоритизации-в-коллекции).

### #️⃣ Использование через php атрибуты

```php
// Определение классов
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

class RuleC {}
```

```php
use App\Rules\RuleInterface;
use Kaspi\DiContainer\Attributes\TaggedAs;

namespace App\Services;

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
use Kaspi\DiContainer\DiContainerFactory;
use App\Rules\{RuleA, RuleB, RuleC};
use App\Services\SrvRules;
use function Kaspi\DiContainer\diAutowire;

// Объявить классы 
$definitions = [

    diAutowire(RuleA::class),

    diAutowire(RuleB::class),

    diAutowire(RuleC::class),

];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(SrvRules::class);
// теперь в свойстве `SrvRules::$rules` содержится итерируемая коллекция
// из классов RuleA, RuleB - так как они имплементируют RuleInterface
```
> 📝 При таком вызове порядок элементов коллекции
> сервисов не определен и может быть любым.
>
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

> 🚩 Метод указанный в `priorityMethod` и `priorityDefaultMethod`
> должен быть объявлен как `public static function`
> и возвращать тип `int`, `string` или `null`.
> В качестве аргументов метод принимает два параметра:
>  - `string $tag` - имя тега;
>  - `array $options` - метаданные тега;
> 
> Эти параметры метода можно использовать в методе для построения логики выдачи `priority`.

### Опция `priority`  для приоритизации в коллекции.

#### 🐘 В стиле php определений

Использовать аргумент `$priority` у [метода `bindTag`](#-объявление-тега-в-стиле-php-определений) как указание приоритета:

```php
// Определение классов
namespace App\Rules;

interface RuleInterface {}

class RuleA implements RuleInterface {}

class RuleB implements RuleInterface {}

class RuleC implements RuleInterface {}
```

```php
use function \Kaspi\DiContainer\diAutowire;
use function \Kaspi\DiContainer\diTaggedAs;

$definitions = [
   diAutowire(App\Rules\RuleA::class)
        ->bindTag(name: 'tags.rules', priority: 10),
   
   diAutowire(App\Rules\RuleB::class)
        ->bindTag(name: 'tags.rules'),

   diAutowire(App\Rules\RuleC::class)
        ->bindTag(name: 'tags.rules', priority: 100),

    diAutowire(App\Rules\Rules::class)
        ->bindArguments(rules: diTaggedAs('tags.rules'))     
];
// при получении коллекции в Rules::$rules отсортированные по приоритету
// 1 - RuleC - priority === 100
// 2 - RuleA - priority === 10
// 3 - RuleB - priority === null
```
#### #️⃣ Через php атрибуты

Использовать аргумент `$priority` у php атрибута `#[Tag]`
как указание приоритета:

```php
// Определение классов
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;

interface RuleInterface {}

#[Tag(name: 'tags.rules', priority: 10)]
class RuleA implements RuleInterface {}

#[Tag(name: 'tags.rules')]
class RuleB implements RuleInterface {}

#[Tag(name: 'tags.rules', priority: 100)]
class RuleC {} implements RuleInterface {}

class Rules {

    public function __construct(
        #[TaggedAs('tags.rules')]
        private iterable $rules
    ) {}

}
```

```php
use function Kaspi\DiContainer\diAutowire;

$definitions = [

   diAutowire(App\Rules\RuleA::class),

   diAutowire(App\Rules\RuleB::class),

   diAutowire(App\Rules\RuleC::class),

];
// при получении коллекции в Rules::$rules отсортированные по приоритету
// 1 - RuleC - priority === 100
// 2 - RuleA - priority === 10
// 3 - RuleB - priority === null
```

### `priorityMethod` и `priorityDefaultMethod` для приоритизации в коллекции.
Указать приоритет тега в коллекции `priority` можно альтернативным способами если определение в контейнере является php-классом:

- `priorityMethod` – метод возвращающий `priority` у тегированного php класса указанный при объявлении тега;
- `priorityDefaultMethod` – метод указанный через
[хэлпер функцию `diTaggedAs`](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#ditaggedas)
или через [php атрибут #[TaggedAs]](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#taggedas)
который **может быть реализован** в тегированном php классе возвращающий `priority`.
 
#### 🐘 В стиле php определений

Использование метаданных в аргументе `$options` – указав в массиве ключ `priority.method` [у метода `bindTag`](#-объявление-тега-в-стиле-php-определений)
как указание приоритета:
```php
['priority.method' => 'methodName']
```
Пример использования значения `priority.method`:
```php
// Определение классов
namespace App\Rules;

interface RuleInterface {
    public static function getPriority(): int;
}

class RuleA implements RuleInterface {

    public static function getPriority(): int {
        return 10;
    }

}

class RuleB implements RuleInterface {

    public static function getPriority(): int {
        return 0;
    }

}

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
use function \Kaspi\DiContainer\diAutowire;
use function \Kaspi\DiContainer\diTaggedAs;

$definitions = [
   diAutowire(App\Rules\RuleA::class) // реализует метод интерфейса RuleInterface::getPriority
        ->bindTag(
            name: 'tags.rules',
            options: ['priority.method' => 'getPriority']
        ),

   diAutowire(App\Rules\RuleB::class) // реализует метод интерфейса RuleInterface::getPriority
        ->bindTag(
            name: 'tags.rules',
            options: ['priority.method' => 'getPriority']
        ),

   diAutowire(App\Rules\RuleC::class)
        ->bindTag(name: 'tags.rules'), // не указываем явно данные как получать `priority`
                                       // метод по умолчанию будет указан в `diTaggedAs`.

    diAutowire(App\Rules\Rules::class)
        ->bindArguments(
            rules: diTaggedAs(
                'tags.rules',
                // если нет `priority` и `priority.method`
                // попробовать вызвать метод - className::getPriorityForCollection() 
                priorityDefaultMethod: 'getPriorityForCollection'
            )
        )
];
// при получении коллекции в Rules::$rules отсортированные по приоритету
// 1 - RuleC::getPriorityForCollection() === 100
// 2 - RuleA::getPriority() === 10
// 3 - RuleB::getPriority() === 0
```
### #️⃣ Через php атрибуты

Использование аргумент `$priorityMethod` у php атрибута `#[Tag]`
как указание приоритета:

```php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;

#[Tag(name: 'tags.rules', priorityMethod: 'getPriority')]
class RuleA {

    public static function getPriority(): int {
        return 10;
    }

}

#[Tag(name: 'tags.rules', priorityMethod: 'getPriority')]
class RuleB {

    public static function getPriority(): int {
        return 0;
    }

}

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

$definitions = [

   diAutowire(App\Rules\RuleA::class),

   diAutowire(App\Rules\RuleB::class),

   diAutowire(App\Rules\RuleC::class),

];
// при получении коллекции отсортированные по приоритету
// 1 - RuleC::getPriorityForCollection() === 100
// 2 - RuleA::getPriority() === 10
// 3 - RuleB::getPriority() === 0 
```

## Использование ключей в коллекции.

По умолчанию в качестве ключей элементов в коллекции используются идентификаторы
определений в контейнере (_container identifier - не пустая строка_).

> ⚠ Если в коллекции тегированных определений встречаются одинаковые
> ключи, то в коллекцию попадёт определение с более высоким приоритетом (`priority`),
> остальные определения с таким же значением ключа будут игнорированы.

"Ленивая" (`$isLazy = true`) коллекция реализует следующие интерфейсы:

- `\Iterator`
- `\Psr\Container\ContainerInterface`
- `\ArrayAccess`
- `\Countable` 

что даёт возможность доступа к элементам коллекции
по именам ключей в стиле php массивов или в стиле `ContainerInterface`

Пример доступа по имени ключа:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Attributes\Tag

#[Tag('tags.tag_one', options: ['key_as' => 'write'])]
class DoWrite {}

#[Tag('tags.tag_one', options: ['key_as' => 'read'])]
class DoRead {}

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

> ℹ В стиле php массивов так же можно использовать
> функции `isset`, `count`. В стиле `ContainerInterface`
> доступны методы `has` и `get`

> 💥 Если сервис не найден по запрошенному ключу
> будет выброшено исключение реализующее
> интерфейс `Psr\Container\NotFoundExceptionInterface`.

### Ключ в коллекции как целое число.
Для получения в качестве ключей коллекции целых чисел (_последовательные значения от нуля и больше_)
нужно указать в аргументе `$useKeys=false`.

Для хэлпер функции `diTaggedAs`:

```php
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definition = [
    diAutowire(ClassTaggedAs::class)
        ->bindArguments(
            diTaggedAs('tags.tag_one', useKeys: false) // ключи целые числа от 0 до n
        )
];
```
Для php атрибута `#[TaggedAs]`:
```php
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
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

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

// ...

$definition = [
    diAutowire(ServiceOne::class)
        ->bindTag('tags.tag_one', options: ['key_as' => 'foo']),
    
    diAutowire(ServiceTwo::class)
        ->bindTag('tags.tag_one', options: ['key_as' => 'baz']),

    diAutowire(ClassTaggedAs::class)
        ->bindArguments(
            diTaggedAs('tags.tag_one', key: 'key_as') // ключ будет получен из метаданных тега
        ),
];
```
#️⃣ Для php атрибута `#[TaggedAs]`:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'])]
class ServiceOne {}

#[Tag('tags.tag_one', options: ['key_as' => 'baz'])]
class ServiceTwo {}

class ClassTaggedAs {
    public function __construct(
        #[TaggedAs('tags.tag_one', key: 'key_as')] // ключ будет получен из метаданных тега
        private iterable $items
    ) {}
    
    public function doFoo() {
        $this->items->get('foo'); // в стиле ContainerInterface
    }
    
    public function doBaz() {
        $this->items['baz']; // в стиле php массива
    }
}

// ...

$definition = [
    diAutowire(ServiceOne::class),
    
    diAutowire(ServiceTwo::class),
];
```
⚖ Пример поведения коллекции если значения ключей совпадают:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'])]
class ServiceOne {}

#[Tag('tags.tag_one', options: ['key_as' => 'foo'], priority: 100)]
class ServiceTwo {}

class ClassTaggedAs {
    public function __construct(
        #[TaggedAs('tags.tag_one', key: 'key_as')] // ключ будет получен из метаданных тега
        private iterable $items
    ) {}
}
```
ℹ так как оба тегированных класса содержат в метаданных (в `$options`) одинаковые значения `'key_as' => 'foo'`,
то при получении коллекции в свойство `ClassTaggedAs::$items` по тегу будет учтён приоритет определения (`priority`). У класса `ServiceTwo`
приоритет `100`, а у класса `ServiceOne` приоритет не определен – таким образом при одинаковых ключах у определений
в коллекцию попадёт только `ServiceTwo` у которого приоритет `100`.

### Ключ из метаданных тега через метод класса.
Если необходимо получать ключ определения в коллекцию более сложным образом, то для php класса можно определить метод
через который будет получено значение ключа для коллекции.
Чтобы объявить метод для получения ключа необходимо чтобы строка в значении начиалась с `self::`
и после двоеточия имя метода который реализует получение ключа для коллекции.

Пример ключа из метода:
`['some_key' =>'self::methodName']`.

⚠ Метод реализующий получение ключа должен быть объявлен как `public static function` и возвращать тип `string`.
В качестве аргументов метод принимает два параметра:
 - `string $tag` - имя тега;
 - `array $options` - метаданные тега;

Пример объявления и реализации метода у класса для получения ключа коллекции:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;
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

#[Tag('tags.tag_one', options: ['key_as' => 'foo'])]
class ServiceTwo {}

class ClassTaggedAs {
    public function __construct(
        #[TaggedAs('tags.tag_one', key: 'key_as')] // ключ будет получен из метаданных тега
        private iterable $items
    ) {}
}
```
ℹ при таком объявлении коллекция в свойстве `ClassTaggedAs::$items` класс `ServiceOne` будет иметь ключ `qux` полученный из метода `ServiceOne::getKey()`,
класс `ServiceTwo` будет иметь ключ `foo` полученный их метаданных тега (_значения в `$options`_).

### Ключ из метода класса по умолчанию.
Так же предусмотрено объявление метода для php класса через который будет произведена попытка получить ключ для коллекции,
если у тегированного определения не указан ключ для коллекции в метаданных (_в `$options`_).

Указать метод получения ключа по умолчанию можно в агрументе
`$keyDefaultMethod` [в хэлпер функции diTaggedAs](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#ditaggedas)
или у [php атрибута #[TaggedAs]](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#taggedas).

⚠ Метод реализующий получение ключа должен быть объявлен как `public static function` и возвращать тип `string`.
В качестве аргументов метод принимает два параметра:
- `string $tag` - имя тега;
- `array $options` - метаданные тега;

Пример получения ключа коллекции по интерфейсу:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;
use Kaspi\DiContainer\Attributes\Tag;

class ServiceOne implements ServiceInterface {
    public static function getServiceKey(): string {
        return 'bar';
    }
}

class ServiceTwo implements ServiceInterface {
    public static function getServiceKey(): string {
        return 'foo';
    }
}

class ClassTaggedAs {
    public function __construct(
        #[TaggedAs(ServiceInterface::class, priorityDefaultMethod: 'getServiceKey')]
        private iterable $items
    ) {}
}
```
ℹ при таком объявлении коллекция в свойстве `ClassTaggedAs::$items` класс `ServiceOne` с ключём `bar` полученный из метода `ServiceOne::getServiceKey()`,
`ServiceTwo` с ключём `foo` полученный из метода `ServiceTwo::getServiceKey()`.
