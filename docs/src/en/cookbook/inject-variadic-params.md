---
outline: [3, 5]
title: Dependency injection into variable-length parameters
---

### Dependency injection into variable-length parameters { #overview }

При внедрении зависимостей в параметр переменной длины можно использовать комбинации хелпер функций или PHP атрибутов. Проверка типа[^TypeHints] внедряемой зависимости происходит в рантайм – в момент выполнения, контейнер не выполняет валидацию типов при [сборке контейнера](../documentation/container-builder/index.md).

> [!WARNING]
> Параметр переменной длины является опциональным. Если не задан аргумент, то он будет пропущен при внедрении зависимости.

Рассмотрим пример объявления внедрения в параметр переменной длины.

### PHP-классы { #php-classes }

::: code-group
<<< @/_code/cookbook/inject-variadic-params/src/Services/Rules.php
<<< @/_code/cookbook/inject-variadic-params/src/Interfaces/RuleInterface.php
<<< @/_code/cookbook/inject-variadic-params/src/Rules/RuleA.php
<<< @/_code/cookbook/inject-variadic-params/src/Rules/RuleB.php
<<< @/_code/cookbook/inject-variadic-params/src/Rules/RuleC.php
:::

Необходимо объявить внедрение в параметр конструктора переменной длины `$inputRule` PHP-класса `\App\Services\Rules`. Внедрение PHP-классов в следующем порядке:
- `\App\Rules\RuleB`
- `\App\Rules\RuleA`
- `\App\Rules\RuleC`

Контейнер предоставляет множество способов объявления внедрения PHP-класса в параметр, для этого примера будет использована [хелпер функция `diGet()`](../documentation/php-definition/di-get.md) или [атрибут `\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) в зависимости от способа объявления внедрения зависимости.

---

#### Объявление в файлах конфигураций { #php-definition }

В файле конфигураций `services.php` объявить PHP-класс `\App\Services\Rules` через хелпер функцию `diAutowire()` и внедрение зависимостей в параметр `$inputRule` в [методе `bindArguments()`](../documentation/php-definition/di-autowire.md#bindarguments).

::: code-group
<<< @/_code/cookbook/inject-variadic-params/container_build.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Services\Rules;
use App\Rules\{RuleC, RuleB, RuleA};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield 'services.rule_c' => diAutowire(RuleC::class);//[!code highlight]
    
    // many lines

    yield diAutowire(Rules::class)
        ->bindArguments(//[!code ++]
            diGet(RuleB::class),//[!code ++]
            diGet(RuleA::class),//[!code ++]
            diGet('services.rule_c'), // получение по идентификатору контейнера//[!code ++]
        );//[!code ++]
};
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
array(3) {
  [0]=>
  object(App\Rules\RuleB)#26 (0) {
  }
  [1]=>
  object(App\Rules\RuleA)#28 (0) {
  }
  [2]=>
  object(App\Rules\RuleC)#21 (0) {
  }
}
```

Порядок объектов внедренных через конструктор в свойство `\App\Services\Rules::$rules` обусловлен порядком объявления внедрения в файле конфигураций `services.php`.


##### Именованные аргументы { #named-arguments }

Предположим что в свойство `\App\Services\Rules::$rules` необходимо внедрить PHP-классы и в качестве ключа массив использовать строковое значение. Для этого в файле конфигураций `services.php` при объявлении внедрения в параметр конструктора `$inputRule` будем использовать именованные аргументы.

При объявлении внедрения с использованием [именованных аргументов](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments) для [параметров переменной длины](https://www.php.net/manual/en/functions.arguments.php#functions.variable-arg-list) действуют правила описанные в PHP-документации.

::: code-group
<<< @/_code/cookbook/inject-variadic-params/container_build.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Services\Rules;
use App\Rules\{RuleB, RuleA};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Rules::class)
        ->bindArguments(//[!code ++]
            inputRule_B: diGet(RuleB::class),//[!code ++]
            inputRule_A: diGet(RuleA::class),//[!code ++]
        );//[!code ++]
};
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console{2,5}
array(2) {
  ["inputRule_B"]=>
  object(App\Rules\RuleB)#26 (0) {
  }
  ["inputRule_A"]=>
  object(App\Rules\RuleA)#28 (0) {
  }
}
```

При использовании строковых ключей в свойстве `\App\Services\Rules::$rules` можно использовать доступ к внедрённым классам указанному имени аргумента:
```php
$rules = $container->get(Rules::class);

$ruleA = $rules->rules['inputRule_A'];
$ruleB = $rules->rules['inputRule_B'];
```

> [!TIP]
> Для внедрения коллекций предпочтительнее использовать [теги](../documentation/tags/index.md).

---

#### Объявление через атрибут { #php-attributes }

Объявим внедрение в параметр конструктора `$inputRule` PHP-класса `\App\Services\Rules` через [атрибут `\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md).

::: code-group

```php [Rules.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Rules.php [!code highlight]

namespace App\Services;

use App\Interfaces\RuleInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]
use App\Rules\{RuleB, RuleA, RuleC};//[!code ++]

final class Rules
{
    /** @var RuleInterface[] */
    public readonly array $rules;//[!code highlight]

    public function __construct(
        #[Inject(RuleB::class)]//[!code ++]
        #[Inject(RuleA::class)]//[!code ++]
        #[Inject(RuleC::class)]//[!code ++]
        RuleInterface ...$inputRule
    ) {
        $this->rules = $inputRule;
    }
}

```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Rules;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$rules = $container->get(Rules::class);
var_dump($rules->rules);
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
array(3) {
  [0]=>
  object(App\Rules\RuleB)#26 (0) {
  }
  [1]=>
  object(App\Rules\RuleA)#28 (0) {
  }
  [2]=>
  object(App\Rules\RuleC)#21 (0) {
  }
}
```

Порядок объектов внедренных через конструктор в свойство `\App\Services\Rules::$rules` обусловлен порядком объявления внедрения через атрибут.

<!--@include: ../documentation/_include/term_notes.md-->