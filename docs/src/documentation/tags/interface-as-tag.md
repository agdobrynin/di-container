---
outline: [2, 4]
---
# PHP интерфейс как имя тега { #title }

## Обзор { #overview }

В качестве имени тега можно использовать FQCN[^FQCN] интерфейса реализуемого классами.

Объявление внедрения коллекции делается через [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

<!--@include: ../_include/need_import_php_class.md-->

В качестве примера рассмотрим класс `\App\Services\FooValidator`:

::: code-group

<<< @/_code/src/Services/FooValidator.php

<<< @/_code/src/Interfaces/RuleInterface.php

:::

Необходимо объявить внедрение коллекции из PHP классов реализующих интерфейс `\App\Interfaces\RuleInterface` в параметр конструктора класс `$rules`.

Представлены PHP классы выполняющие некую валидацию данных:

::: code-group

<<< @/_code/src/ValidateRules/RuleA.php

<<< @/_code/src/ValidateRules/RuleB.php

<<< @/_code/src/ValidateRules/RuleC.php

:::

Важное замечание – PHP класс `\App\ValidateRules\RuleB` не реализует интерфейс `\App\Interfaces\RuleInterface` через ключевое слово `implements`.

### Объявление в файлах конфигураций  { #as-php-definition }

В [файлах конфигураций](../container-builder/configuration_files.md) необходимо указать в параметре `$tag` хелпер функции `diTaggedAs()` FQCN[^FQCN] интерфейса – `\App\Interfaces\RuleInterface`.

::: code-group

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Interfaces\RuleInterface;
use App\Services\FooValidator;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(FooValidator::class)
        ->bindArguments(
            rules: diTaggedAs(RuleInterface::class)//[!code ++]
        );
};
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\FooValidator;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$validator = $container->get(FooValidator::class);

foreach ($validator->rules as $rule) {
    var_dump($rule::class);
}

// console output:
// string (22) "App\ValidateRules\RuleA"
// string (22) "App\ValidateRules\RuleC"
```
<<< @/_code/src/Services/FooValidator.php

<<< @/_code/src/ValidateRules/RuleA.php

<<< @/_code/src/ValidateRules/RuleB.php

<<< @/_code/src/ValidateRules/RuleC.php

::: 

Класс `\App\ValidateRules\RuleB` не попал в коллекцию так как не реализует интерфейс `App\Interfaces\RuleInterface`.

> [!NOTE]
> Очерёдность элементов в коллекции не определена при такой конфигурации, и может быть любой.

### Объявление через атрибуты { #as-php-attribute }

В атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs` необходимо указать в параметре `$name` FQCN интерфейса – `\App\Interfaces\RuleInterface`.

::: code-group

<<< @/_code/src/Services/FooValidatorAttr.php [FooValidator.php]

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\FooValidator;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$validator = $container->get(FooValidator::class);

foreach ($validator->rules as $rule) {
    var_dump($rule::class);
}

// console output:
// string (22) "App\ValidateRules\RuleA"
// string (22) "App\ValidateRules\RuleC"
```

<<< @/_code/src/ValidateRules/RuleA.php

<<< @/_code/src/ValidateRules/RuleB.php

<<< @/_code/src/ValidateRules/RuleC.php

::: 

Класс `\App\ValidateRules\RuleB` не попал в коллекцию так как не реализует интерфейс `App\Interfaces\RuleInterface`.

> [!NOTE]
> Очерёдность элементов в коллекции не определена при такой конфигурации, и может быть любой.

## Очерёдности элементов коллекции { #priority-by-method-default }

При использовании FQCN[^FQCN] интерфейса в качестве имени тега внедряемая коллекция имеет неопределённую очередность элементов (сортировка элементов). 

Для изменения очерёдности элементов такой коллекции можно воспользоваться параметром `$priorityDefaultMethod` [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md), указав «метод-приоритета» по умолчанию.

Необходимо добавить статический метод в нужный PHP класс, который будет возвращать приоритет в коллекции:

```php
public static function getPriority(): int // [!code ++]
{// [!code ++]
    // Для каждого класса свой приоритет// [!code ++]
    return 100;// [!code ++]
}// [!code ++]
```

Если «метод-приоритета» по умолчанию не определен в PHP классе, который является элементом коллекции, то значение приорита будет `null`.

Для хелпер функции `diTaggedAs()` указать метод-приоритета `getPriority` :

```php
<?php
declare(strict_types=1);

// file: /app/config/services.php

use App\Interfaces\RuleInterface;
use App\Services\FooValidator;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(FooValidator::class)
        ->bindArguments(
            rules: diTaggedAs(
                RuleInterface::class,
                priorityDefaultMethod: 'getPriority',//[!code ++]
            )
        );
};
```

Для атрибута `\Kaspi\DiContainer\Attributes\TaggedAs` указать метод-приоритета `getPriority`:

```php
<?php
declare(strict_types=1);

// file: /app/src/Services/FooValidator.php //[!code highlight]

namespace App\Services;

use App\Interfaces\RuleInterface;
use Kaspi\DiContainer\Attributes\TaggedAs;

final class FooValidator
{
    /**
     * @param iterable<RuleInterface> $rules
     */
    public function __construct(
        #[TaggedAs(
            RuleInterface::class,
            priorityDefaultMethod: 'getPriority',//[!code ++]
        )]
        public readonly iterable $rules
    ) {}

    public function validate(mixed $input): mixed
    {
        foreach ($this->rules as $rule) {
            $input = $rule->validate($input);
        }

        return $input;
    }
}
```

⬆️ Изучите информацию в разделе документации [«Приоритет элементов коллекции»](collection-priority/index.md).

<!--@include: ../_include/term_notes.md-->