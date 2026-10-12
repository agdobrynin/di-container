---
outline: [2, 4]
---
# ⬆️ Приоритет элементов коллекции { #title }

## Обзор { #overview }

Приоритет определяет как будут отсортированы элементы в получаемой коллекции. Значение приоритета может быть типам `int`, `string`, `null`.

**Чем больше значение приоритета, тем выше сервис будет расположен в коллекции.**

Сравнение может быть как целых чисел, так и строк. Сравнение строк происходит как последовательности байтов строк.

Очерёдность получения приоритета у элемента коллекции в порядке возрастания значимости:

1. **Приоритет тега**, параметр `$priority`:
   - в стиле PHP определений – метод `bindTag()` [^BindTag]
   - через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).
2. «**Метод-приоритета**» тега для элемента коллекции PHP класс, если неустановлен приоритет тега:
   - в стиле PHP определений через [метаданные](tag-metadata.md) тега в параметре `$options` метода `bindTag()` [^BindTag].
   - через [атрибут `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`](../attribute-definition/tag.md).
3. «**Метода-приоритета» по-умолчанию** тега для элемента коллекции PHP класс, если неустановлен «**метод-приоритета**» тега:
   - в стиле PHP определений – параметр `$priorityDefaultMethod` в [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md).
   - через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs::$priorityDefaultMethod`](../attribute-definition/tagged-as.md).


> [!IMPORTANT]
> «Метод-приоритета» и «метод-приоритета» по-умолчанию должен быть объявлен с модификаторами `public` и `static`. Возвращаемые значения метода – `int`, `string` или `null`.
>
> В качестве параметров метод принимает два аргумента передаваемые контейнером:
>  - `string $tag` – имя тега.
>  - `array $options` – [метаданные тега](tag-metadata.md).
>
> Эти параметры метода можно использовать построения логики выдачи приоритета тега.

## Приоритет тега { #priority-by-tag }

Приоритет тега в коллекции задаётся непосредственно в указанном теге.
В стиле PHP определений приоритет указываться в параметре `$priority` метода `bindTag()`[^BindTag],
через PHP атрибуты приоритет указывается в параметре [атрибута `\Kaspi\DiContainer\Attributes\Tag::$priority`](../attribute-definition/tag.md).

### Пример конфигурации { #example-priority-by-tag }

Необходимо внедрить коллекцию в параметр `\App\Services\Foo::$services` в определенном порядке:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(public readonly iterable $services) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    // …
}
```

:::

#### Конфигурация в стиле php определений { #priority-by-tag-php-definition }

Используем значение в параметре `$priority` метода `bindTag()`[^BindTag]

Конфигурационный файл:

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.ordered')
        );

    yield diAutowire(Bar::class)
        ->bindTag(name: 'tags.ordered');
   
   yield diAutowire(Baz::class)
        ->bindTag(name: 'tags.ordered', priority: 10);

   yield diAutowire(Qux::class)
        ->bindTag(name: 'tags.ordered', priority: 100);
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/tagged_foo_service.php')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}
// вывод в консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

Порядок сортировки во внедрённой коллекции обусловлен значениями `$prioriy` для каждого тега.

#### Конфигурация через атрибуты { #priority-by-tag-attributes }

Добавим к PHP классам [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md), для внедрения коллекции [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.ordered')]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered', priority: 10)]// [!code ++]
final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered', priority: 100)]// [!code ++]
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
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}
// вывод в консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

Порядок сортировки во внедрённой коллекции обусловлен значениями в атрибутах `\Kaspi\DiContainer\Attributes\Tag::$prioriy` для каждого тега.

## «Метод-приоритета» { #priority-by-method }

Для элементов внедряемой коллекции представленных PHP классом приоритет в коллекции может быть задан через «метод-приоритета».

«**Метод-приоритета**» задаётся непосредственно в теге элемента коллекции.

В стиле PHP определений «метод-приоритета» через [метаданные](tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag].
Имя ключа в метаданных должно быть представлено строкой `'priority.method'`, значение имя метода.
```php
use Generator;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindTag(
            name: 'tags.tag_name',
            options: [
               'priority.method' => 'staticMethodName',
            ]
        );
};
```

Через PHP атрибуты «метод-приоритета» указывается в параметре атрибута `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`.

```php
use Kaspi\DiContainer\Attributes\Tag;

#[Tag(
    name: 'tags.tag_name',
    priorityMethod: 'getPriority',
)]
final class Foo
{
    // …
    public static function getPriority(): int
    {
        return 100;
    }  
}
```

### Пример конфигурации { #example-priority-by-method }

Необходимо внедрить коллекцию в параметр `\App\Services\Foo::$services` в определенном порядке:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

🚧🚧🚧🚧🚧 задача указать метод-приоритета, подумать как использовать tagName и options.

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(public readonly iterable $services) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    // …
}
```

:::


#### Конфигурация в стиле php определений { #example-priority-by-method-php-definitions }

#### Конфигурация через атрибуты { #example-priority-by-method-attributes }

## «Метод-приоритета» по-умолчанию { #priority-by-method-default }

#### Конфигурация в стиле php определений { #example-priority-by-method-default-php-definitions }

#### Конфигурация через атрибуты { #example-priority-by-method-default-attributes }

----------------

🚧🚧🚧🚧🚧🚧

----------------

## 🐘 В стиле php определений

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

## #️⃣ Через php атрибуты

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
