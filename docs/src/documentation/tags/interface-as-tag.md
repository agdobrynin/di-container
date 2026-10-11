# 📑 PHP интерфейс как имя тега { #title }

## Обзор { #overview }

В качестве имени тега можно использовать имя интерфейса (**FQCN – Fully Qualified Class Name**)
реализуемого классами. Чтобы такой подход сработал необходимо
чтобы класс реализующий запрашиваемый интерфейс был объявлен
в контейнере.

> [!IMPORTANT]
> #️⃣ При использовании тегирования через PHP атрибуты необходимо чтобы
> класс был зарегистрирован в контейнере. Если сервис не зарегистрирован напрямую в контейнере
> используйте [импорт классов из директорий проекта через `DiContainerBuilder::import()`](../00-container-builder.md).

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
> аргументом `$priorityDefaultMethod` у хелпер функции:
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
