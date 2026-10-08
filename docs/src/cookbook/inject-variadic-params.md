---
outline: [2, 3]
---

# Внедрение зависимостей в параметры переменной длины

## Обзор { #overview }

При внедрении зависимостей в параметр переменной длины можно использовать комбинации 
хелпер функций или PHP атрибутов. Проверка типа (_type hints_) внедряемой зависимости производится на уровне вызова метода или функции – в момент выполнения.

> [!WARNING]
> Параметр переменной длины является опциональным. Если не задан аргумент, то он будет пропущен при внедрении зависимости.

::: code-group

```php [RuleGenerator.php]
// file: /app/src/Rules/RuleGenerator.php

namespace App\Rules;

class RuleGenerator {

    private array $rules;

    public function __construct(RuleInterface ...$inputRule)
    {
        $this->rules = $inputRule;
    }
    
    public function getRules(): array
    {
        return $this->rules;
    }
}
```

```php [RuleInterface.php]
// file: /app/src/Rules/RuleInterface.php
namespace App\Rules;

interface RuleInterface {}
```

```php [RuleA.php]
// file: /app/src/Rules/RuleA.php
namespace App\Rules;

class RuleA implements RuleInterface {}
```

```php [RuleB.php]
// file: /app/src/Rules/RuleB.php
namespace App\Rules;

class RuleB implements RuleInterface {}
```

```php [RuleC.php]
// file: /app/src/Rules/RuleC.php
namespace App\Rules;

class RuleC implements RuleInterface {}
```

:::

## PHP определения { #php-definition }

Конфигурирование в стиле PHP определений.

Конфигурирование:

```php
// file: /app/config/services.php
use function Kaspi\DiContainer\{diAutowire, diGet};
use App\Rules\{RuleA, RuleB, RuleC, RuleGenerator};

return static function (): \Generator {

    yield 'ruleC' => diAutowire(RuleC::class);

    yield diAutowire(RuleB::class);

    yield diAutowire(RuleA::class);

    yield diAutowire(RuleGenerator::class)
        ->bindArguments(
            diGet(RuleB::class),
            diGet(RuleA::class),
            diGet('ruleC'), // <-- получение по идентификатору контейнера
        )
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Rules\RuleGenerator;
use App\Rules\{RuleA, RuleB, RuleC};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build();

$ruleGenerator = $container->get(RuleGenerator::class);
$rules = $ruleGenerator->getRules();

/*
 * Порядок объектов определен
 * в конфигурационном файле 'services.php'
 */ 

var_dump($rules[0] instanceof RuleB);
// (bool) true

var_dump($rules[1] instanceof RuleA);
// (bool) true

var_dump($rules[2] instanceof RuleC);
// (bool) true
```

### Именованные аргументы { #named-arguments }

При использовании [именованных аргументов](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)
для [параметров переменной длины](https://www.php.net/manual/ru/functions.arguments.php#functions.variable-arg-list)
действуют правила описанные в документации php.

Конфигурирование:

```php
// file: /app/config/services.php

use function Kaspi\DiContainer\{diAutowire, diGet};
use App\Rules\{RuleA, RuleB};

return static function (): \Generator {
    yield diAutowire(RuleA::class);

    yield diAutowire(RuleB::class);

    yield diAutowire(RuleGenerator::class)
        ->bindArguments(
            inputRule_B: diGet(RuleB::class),
            inputRule_A: diGet(RuleA::class),
        )
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Rules\RuleGenerator;
use App\Rules\RuleB;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build();

$ruleGenerator = $container->get(RuleGenerator::class);

/*
 * В классе `RuleGenerator` ключи аргументов сохраняются,
 * поэтому доступ к элементам массива можно осуществить
 * по имени аргумента указанному в конфигурационном файле
 */

$rules = $ruleGenerator->getRules();

var_dump($rules['inputRule_B'] instanceof RuleB);
// (bool) true
var_dump($rules['inputRule_A'] instanceof RuleA);
// (bool) true
```

## PHP атрибуты { #php-attributes }

Конфигурирование через PHP атрибуты.

Конфигурирование класса: { #config-attr }

::: code-group

```php [RuleGenerator.php]
// file: /app/src/Rules/RuleGenerator.php

namespace App\Rules;

use Kaspi\DiContainer\Attributes\Inject;

class RuleGenerator {

    private array $rules;

    public function __construct(
        #[Inject(RuleB::class)]
        #[Inject(RuleA::class)]
        #[Inject(RuleC::class)]
        RuleInterface ...$inputRule
    ) {
        $this->rules = $inputRule;
    }
    
    public function getRules(): array
    {
        return $this->rules;
    }
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Rules\RuleGenerator;
use App\Rules\{RuleA, RuleB, RuleC};

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->build();

$ruleGenerator = $container->get(RuleGenerator::class);

$rules = $ruleGenerator->getRules();

var_dump($rules[0] instanceof RuleB);
// (bool) true

var_dump($rules[1] instanceof RuleA);
// (bool) true

var_dump($rules[2] instanceof RuleC);
// (bool) true
```
[Порядок элементов](#config-attr) определен последовательностью применения атрибута `Inject` к свойству `RuleGenerator::$inputRule`.