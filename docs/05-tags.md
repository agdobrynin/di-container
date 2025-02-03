# 🔖 Работа с тегами в контейнере
Теги позволяют расширить возможности работы с зарегистрированными сервисами,
собирая сервисы в коллекции (_списки_) и применяется для параметров с типом `iterable` и `array`.

Любое определение в контейнере может быть отмечено
одним или несколькими тегами.
Каждый тег может содержать мета-данные переданные в виде массива.

Тегирование сервисов можно произвести при объявлении в стиле [php-определений](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md)
или используя [PHP атрибуты](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).

> #️⃣ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере через хэлпер функцию `diAutowire`

Для получения тегированных сервисов на аргументы (_параметры - конструктора, метода или аргументы функции_) нужно использовать:
- `diTaggedAs` - [функцию хэлпер](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#ditaggedas) при php определениях 
- `#[TaggedAs]` - [php атрибут](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md#taggedas) 

### Ленивая коллекция
Особенности получения коллекции в том что по-умолчанию
она будет получена как "ленивая" - инициализация тегированного сервиса происходит
только когда к нему произойдёт обращение.

Для "ленивой" коллекции необходимо чтобы тип параметра
куда будет помещена результат был `iterable`.
В случае если тип параметра `array` куда будет помещён результат
то тогда необходимо отметить что коллекция "не ленивая", таким образом
сервисы будут инициализированы в виде массива.

## Объявление тега через php определение.
Для указания тегов для определения используется метод:

* `bindTag(string $name, array $options)`

Метод `bindTag` доступен через [функции хэлперы](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md#%D0%BE%D0%B1%D1%8A%D1%8F%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B8-%D1%85%D1%8D%D0%BB%D0%BF%D0%B5%D1%80%D1%8B)
которые реализуют интерфейсы 
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface`
- `Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionSetupInterface`

> 📝 метод `bindTag` имеет параметр `$options` со значением по умолчанию
> `['priority' => 0]` описывающее [приоритет сортировки](#приоритет-в-коллекции)
> тегированных определений.

Пример использования с хэлпер-функцией `diAutowire`:
```php
// определение классов
class One {}

class Two {}

class ServicesAny {
    public function __construct(private iterable $services) {}
}
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definitions = [
    diAutowire(One::class)
        ->bindTag(name: 'tags.services-any'),
    diAutowire(Two::class)
        ->bindTag(name: 'tags.services-any'),
    diAutowire(ServicesAny::class)
        ->bindArguments(services: diTaggedAs('tags.services-any')),
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(ServicesAny::class);
// теперь в свойстве `services` содержится итерируемая коллекция
// из классов One, Two
```
> ⚠ Если тип аргумента куда добавляется тегированная коллекция `array`
> то необходимо указать "не ленивое" получение:
> ```php
> use function Kaspi\DiContainer\diTaggedAs;
> 
> diTaggedAs(tag: 'tags.services-any', isLazy: false)
> ```
#### Получение тегированных сервисов можно применять так же **параметрам переменной длинны**:
```php
class AnyService {
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
        ->bindTag(name: 'tags.ser-group-1'),

    diAutowire(Two::class)
        ->bindTag(name: 'tags.ser-group-1'),

    diAutowire(Three::class)
        ->bindTag(name: 'tags.ser-group-2'),

    diAutowire(Four::class)
        ->bindTag(name: 'tags.ser-group-2'),

    diAutowire(AnyService::class)
        ->bindArguments(
            srvGroup: [
                // аргумент имеет тип array то $isLazy=false
                diTaggedAs('tags.ser-group-1', false),
                diTaggedAs('tags.ser-group-2', false),
            ]
        ),
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(AnyService::class);
/**
 * Эквивалент вызова:
 * $class = new AnyService(
 *  [new One, new Two],
 *  [new Three, new Four]
 * )
 */
```

## Объявление тега через php атрибут.
Для указания тегов для класса необходимо использовать php атрибут `#[Tag]`:

```php
use Kaspi\DiContainer\Attributes\Tag; 
namespace App\Any;

#[Tag(name: 'tags.services.group-one')]
#[Tag(name: 'tags.services.group-two')]
class One {}

#[Tag('tags.services.group-two', options: ['priority' => 1000])]
class Two {}
```
Для получения коллекции тегированных сервисов использовать php атрибут `#[TaggedAs]`:
```php
use Kaspi\DiContainer\Attributes\TaggedAs;

namespace App\Services;

class GroupTwo {
    public function __construct(
        #[TaggedAs('tags.services.group-two')]
        private iterable $services
    ) {}
}
```
> #️⃣ При использовании тегирования через PHP атрибуты
> необходимо чтобы класс использующий `#[Tag]` был зарегистрирован
> в контейнере через хэлпер функцию diAutowire

```php
use Kaspi\DiContainer\DiContainerFactory;
use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

$definitions = [
    diAutowire(One::class),
    diAutowire(Two::class),
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(GroupTwo::class);
// теперь в свойстве `services` содержится итерируемая коллекция
// из классов Two, One - такой порядок обусловлен значением 'priority'
```
#### Получение тегированных сервисов можно применять так же **параметрам переменной длинны**:
```php
use Kaspi\DiContainer\Attributes\{Tag, TaggedAs};

class AnyService {
    public function __construct(
        // аргумент имеет тип array то $isLazy=false
        #[TaggedAs('tags.ser-group-1', false)]
        #[TaggedAs('tags.ser-group-2', false)]
        array ...$srvGroup
    ) {}
}

#[Tag('tags.ser-group-1')]
class One {}

#[Tag('tags.ser-group-1')]
class Two {}

#[Tag('tags.ser-group-2')]
class Three {}

#[Tag('tags.ser-group-2')]
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
$class = $container->get(AnyService::class);
/**
 * Эквивалент вызова:
 * $class = new AnyService(
 *  [new One, new Two],
 *  [new Three, new Four]
 * )
 */
```
## Interface как имя тега.
В качестве имени тега можно использовать имя интерфейса (**FQCN**)
реализуемого классами. Чтобы такой подход сработал необходимо
чтобы класс реализующий запрашиваемый интерфейс был объявлен через функцию хэлпер `diAutowire`.

📃 Пример использования через php определения:
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
        ->bindArguments(rules: diTaggedAs(RuleInterface::class))
];

$container = (new DiContainerFactory())->make($definitions);
$class = $container->get(SrvRules::class);
// теперь в свойстве `rules` содержится итерируемая коллекция
// из классов RuleA, RuleB - так как они имплементируют RuleInterface
```
#️⃣ Пример использования через php атрибуты:

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
// теперь в свойстве `rules` содержится итерируемая коллекция (\Generator)
// из классов RuleA, RuleB - так как они имплементируют RuleInterface
```

## Приоритет в коллекции.
Приоритет это положительное или отрицательное целое число,
которое по умолчанию равно 0.
**Чем больше значение приоритета, тем выше сервис будет расположен в коллекции.**

У метода `bindTag` для php-определений и у php атрибута `#[Tag]`
определен параметр `$options` как массив.
В массиве мета-данных ключ `priority` является зарезервированным
с помощью которого сортируются сервисы в коллекции.

Для php-определений:
```php
use function \Kaspi\DiContainer\diAutowire;
use function \Kaspi\DiContainer\diTaggedAs;

$definitions = [
   diAutowire(App\Rules\RuleA::class)
        ->bindTag(name: 'tags.rules', options: ['priority' => 10]),
   //...
   diAutowire(App\Rules\RuleC::class)
        ->bindTag(name: 'tags.rules', options: ['priority' => 100]),
    // ...
    diAutowire(App\Rules\Rules::class)
        ->bindArguments(rules: diTaggedAs('tags.rules'))     
];
// при получении коллекции отсортированные по приоритету
// 1 - RuleC
// 2 - RuleA
```
Для php атрибута:

```php
namespace App\Rules;

use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\Attributes\TaggedAs;use function Kaspi\DiContainer\diAutowire;

#[Tag(name: 'tags.rules', options: ['priority' => 10])]
class RuleA {}

#[Tag(name: 'tags.rules-other', options: ['priority' => 100])]
class RuleB {}

#[Tag(name: 'tags.rules', options: ['priority' => 100])]
class RuleC {}

class Rules {
    public function __construct(
        #[TaggedAs('tags.rules')]
        private iterable $rules
    ) {}
}

$definitions = [
   diAutowire(App\Rules\RuleA::class),
   diAutowire(App\Rules\RuleB::class),
   diAutowire(App\Rules\RuleC::class),
];
// при получении коллекции отсортированные по приоритету
// 1 - RuleC
// 2 - RuleA
```
