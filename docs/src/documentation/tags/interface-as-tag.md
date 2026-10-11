# 📑 PHP интерфейс как имя тега { #title }

## Обзор { #overview }

В качестве имени тега можно использовать FQCN [^FQCN] интерфейса реализуемого классами.

<!--@include: ../_include/need_import_php_class.md-->

## Использование в стиле php определений { #as-php-definition }

PHP классы:

::: code-group

```php [SrvRules.php]
// file: /app/src/Services/SrvRules.php
namespace App\Services;

use App\Interfaces\RuleInterface;

final class SrvRules
{
    /**
    * @param iterable<RuleInterface> $rules
    */
    public function __construct(public readonly iterable $rules) {}

    public function validate(mixed $input): mixed
    {
        foreach ($this->rules as $rule) {
            $input = $rule->validate($input);
        }
        
        return $input;
    }
}
```

```php [RuleInterface.php]
// file: /app/src/Interfaces/RuleInterface.php
namespace App\Interfaces;

interface RuleInterface
{
    public function validate(mixed $input): mixed;
}
```

```php [RuleA.php]
// file: /app/src/Rules/RuleA.php
namespace App\Rules;

use App\Interfaces\RuleInterface;

final class RuleA implements RuleInterface
{
    public function validate(mixed $input): mixed
    {
        // …
    }
}
```

```php [RuleB.php]
// file: /app/src/Rules/RuleB.php
namespace App\Rules;

use App\Interfaces\RuleInterface;

final class RuleB implements RuleInterface
{
    public function validate(mixed $input): mixed
    {
        // …
    }
}
```

```php [RuleC.php]
// file: /app/src/Rules/RuleC.php
namespace App\Rules;

final class RuleC
{
    public function validate(mixed $input): mixed
    {
        // …
    }
}
```

:::

Конфигурация контейнера:

```php
// file: /app/config/services.php
use Generator;
use App\Interfaces\RuleInterface;
use App\Services\SrvRules;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(SrvRules::class)
        ->bindArguments(
            // собрать объявленные классы реализующие интерфейс.
            rules: diTaggedAs(RuleInterface::class)
        );
};

```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$srvRules = $container->get(SrvRules::class);

foreach ($srvRules->rules as $rule) {
    var_dump($rule::class);
}

// string(15) "App\Rules\RuleA"
// string(15) "App\Rules\RuleB"
```

Класс `\App\Rules\RuleC` не попал в коллекцию так как не реализовывает интерфейс `App\Interfaces\RuleInterface`.

> [!NOTE]
> Порядок элементов в коллекции не определен при такой конфигурации и может быть любым.

Для изменения порядка элементов в коллекции можно воспользоваться параметром `$priorityDefaultMethod` хелпер функции `diTaggedAs()`.
Необходимо добавить статический метод в нужный PHP класс, который будет возвращать приоритет в коллекции:

```php
public static function methodPriority(): int // [!code ++]
{ // [!code ++]
    // Для каждого класса свой приоритет // [!code ++]
    return 100; // [!code ++]
} // [!code ++]
```

```php
use App\Interfaces\RuleInterface;
use function Kaspi\DiContainer\diTaggedAs;

diTaggedAs(RuleInterface::class, priorityDefaultMethod: 'methodPriority')
```

⬆️ Подробное описание в разделе документации [«Приоритет элементов коллекции»](collection-priority.md).

## Использование через php атрибуты { #as-php-attribute }

🚧🚧🚧🚧🚧🚧🚧🚧

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

<!--@include: ../_include/term_notes.md-->