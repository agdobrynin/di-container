# ⬆️ Приоритет элементов коллекции { #title }

## Обзор { #overview }

Приоритет определяет как будут отсортированы сервисы в получаемой коллекции. Значение приоритета может быть типам `int`, `string`, `null`.

**Чем больше значение приоритета, тем выше сервис будет расположен в коллекции.**

Сравнение может быть как целых чисел, так и строк. Сравнение строк происходит как последовательности байтов строк.

Очерёдность получения приоритета у элемента коллекции в порядке возрастания значимости:
1. Параметр `$priority` отличное от `null` в методе `bindTag()` [^BindTag], или [атрибуте `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).
2. Если элемент коллекции PHP класс, то будет выполнена попытка получить значение из «метода-приоритета»:
    - при конфигурировании через PHP атрибуты значение в параметре [`\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`](../attribute-definition/tag.md).
    - при конфигурировании в стиле PHP определений, значение передается в параметре `$options` как [метаданные](tag-metadata.md#reserved-keys).
🚧🚧🚧🚧
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
  [хелпер функцию `diTaggedAs`](../03-php-definition.md#ditaggedas)
  или через [php атрибут #[TaggedAs]](../02-attribute-definition.md#taggedas)
  который **может быть реализован** в тегированном php классе возвращающий `priority`.

#### 🐘 В стиле php определений

Использование метаданных в аргументе `$options` – указав в массиве ключ `priority.method` [у метода `bindTag`](#-объявление-тега-в-стиле-php-определений)
как указание приоритета:
```php
['priority.method' => 'staticMethodName']
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

<!--@include: ../php-definition/_include/bind-tag_notes.md-->
