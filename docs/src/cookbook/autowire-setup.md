---
outline: [2, 4]
---
# Внедрение зависимостей через сеттер-методы

## Обзор

Внедрение зависимостей в PHP класс может происходить так же через **сеттер-методы**. Для этого при настройке
определения контейнера используются методы хелпер функции [`diAutowire::setup()`](../documentation/php-definition/di-autowire.md#setup) и  [`diAutowire::setupImmutable()`](../documentation/php-definition/di-autowire.md#setupimmutable),
или PHP атрибуты [`\Kaspi\DiContainer\Attributes\Setup`](../documentation/attribute-definition/setup.md) и [`\Kaspi\DiContainer\Attributes\SetupImmutable`](../documentation/attribute-definition/setup-immutable.md).

<span id="src-class"/>Классы для конфигурирования:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use Psr\Log\LoggerInterface;

class Foo
{
    private LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $new->logger = $logger;
    }

    public function withLogger(LoggerInterface $logger): static
    {
        $new = clone $this;
        $new->logger = $logger;
    
        return $new;
    }
    
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }
}
```

```php [FileLogger.php]
// file: /app/src/Services/FileLogger.php

namespace App\Services;

use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    public function __construct(private string $fileName) {}
    // implement methods from LoggerInterface
}
```

:::

## PHP определения { #php-definition }

Конфигурирование в стиле PHP определений.

### diAutowire::setup()

Внедрение зависимости через сеттер-метод PHP класса [без учёта возвращаемого значения методом](../documentation/php-definition/di-autowire.md#setup) (_mutable setter_).

Конфигурирование [PHP классов](#src-class):

::: code-group

```php [services.php]
// file: /app/config/services/services.php
use function Kaspi\DiContainer\diAutowire;
use App\Services\Foo;

return static function(): \Generator {
    yield diAutowire(Foo::class)
        ->setup('setLogger');

    yield 'priority_queue.get_data' => diAutowire(\SplPriorityQueue::class)
        ->setup('setExtractFlags', [\SplPriorityQueue::EXTR_DATA]);
};
```

```php [loggers.php]
// file: /app/config/services/loggers.php
use function Kaspi\DiContainer\{diAutowire, diParameter};
use App\Services\FileLogger;

return static function(): \Generator {
    yield diAutowire(FileLogger::class)
        ->bindArguments(
            diParameter('files.logger_file')
        );
};
```

```php [parameters.php]
// file: /app/config/parameters/logger.php
return [
    'files.logger_file' => '/var/logs/app_logger.log',
];
```

:::


Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use Psr\Log\LoggerInterface;
use function glob;

$container = (new DiContainerBuilder())
    ->loadParameters(...glob('/app/config/parameters/*.php'))
    ->load(...glob('/app/config/services/*.php'))
    ->build();

$priorityQueue = $container->get('priority_queue.get_data');

var_dump($priorityQueue instanceof \SplPriorityQueue);
// (bool) true

$foo = $container->get(Foo::class);
var_dump($foo->getLogger() instanceof LoggerInterface);
// (bool) true
```

### diAutowire::setupImmutable()

Внедрение зависимости через [сеттер-метод возвращающий экземпляр PHP класса](../documentation/php-definition/di-autowire.md#setupimmutable) (_immutable setter_).

Конфигурирование [PHP классов](#src-class):

::: code-group

```php [services.php]
// file: /app/config/services/services.php
use function Kaspi\DiContainer\diAutowire;
use App\Services\Foo;

return static function(): \Generator {
    yield diAutowire(Foo::class)
        ->setupImmutable('withLogger');
};
```

```php [loggers.php]
// file: /app/config/services/loggers.php
use function Kaspi\DiContainer\{diAutowire, diParameter};
use App\Services\FileLogger;

return static function(): \Generator {
    yield diAutowire(FileLogger::class)
        ->bindArguments(
            diParameter('files.logger_file')
        );
};
```

```php [parameters.php]
// file: /app/config/parameters/logger.php
return [
    'files.logger_file' => '/var/logs/app_logger.log',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use Psr\Log\LoggerInterface;
use function glob;

$container = (new DiContainerBuilder())
    ->loadParameters(...glob('/app/config/parameters/*.php'))
    ->load(...glob('/app/config/services/*.php'))
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->getLogger() instanceof LoggerInterface);
// (bool) true
```

## PHP атрибуты { #php-attributes }

Конфигурирование внедрения через сеттер-методы PHP атрибутами `\Kaspi\DiContainer\Attributes\Setup` и `\Kaspi\DiContainer\Attributes\SetupImmutable`.

### Setup { #attribute-setup }

Конфигурация внедрения зависимости через атрибут `\Kaspi\DiContainer\Attributes\Setup` примененный к методу PHP класса.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\Setup;

class Foo
{
    private LoggerInterface $logger;

    #[Setup] // 🚩 конфигурирование сеттер-метода
    public function setLogger(LoggerInterface $logger): void
    {
        $new->logger = $logger;
    }
    
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }
}
```

```php [FileLogger.php]
// file: /app/src/Services/FileLogger.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\Paraneter;

class FileLogger implements LoggerInterface
{
    public function __construct(
        #[Paraneter('files.logger_file')]
        private string $fileName
    ) {}
    // implement methods from LoggerInterface
}
```

:::

Конфигурация параметров контейнера:

```php
// file: /app/config/parameters/logger.php
return [
    'files.logger_file' => '/var/logs/app_logger.log',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use Psr\Log\LoggerInterface;
use function glob;

$container = (new DiContainerBuilder())
    ->loadParameters(...glob('/app/config/parameters/*.php'))
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->getLogger() instanceof LoggerInterface);
// (bool) true
```

### SetupImmutable { #attribute-setup-immutable }

Конфигурация внедрения зависимости через атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable` примененный к методу PHP класса.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\SetupImmutable;

class Foo
{
    private LoggerInterface $logger;

    #[SetupImmutable] // 🚩 конфигурирование сеттер-метода
    public function withLogger(LoggerInterface $logger): static
    {
        $new = clone $this;
        $new->logger = $logger;
    
        return $new;
    }
    
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }
}
```

```php [FileLogger.php]
// file: /app/src/Services/FileLogger.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\Paraneter;

class FileLogger implements LoggerInterface
{
    public function __construct(
        #[Paraneter('files.logger_file')]
        private string $fileName
    ) {}
    // implement methods from LoggerInterface
}
```

:::

Конфигурация параметров контейнера:

```php
// file: /app/config/parameters/logger.php
return [
    'files.logger_file' => '/var/logs/app_logger.log',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use Psr\Log\LoggerInterface;
use function glob;

$container = (new DiContainerBuilder())
    ->loadParameters(...glob('/app/config/parameters/*.php'))
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->getLogger() instanceof LoggerInterface);
// (bool) true
```

### Настройка нескольких атрибутов Setup { #attribute-setup-multiple }

Внедрение нескольких зависимостей через один сеттер-метод с использованием [атрибута `\Kaspi\DiContainer\Attributes\Setup`](../documentation/attribute-definition/setup.md).

::: code-group

```php [RuleGenerator.php]
// file: /app/src/Services/RuleGenerator.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use App\Rules\{RuleA, RuleB};
use App\Interfaces\RuleInterface;

class RuleGenerator
{

    private array $rules = [];
    
    // …
    
    #[Setup(inputRule: new DiGet(RuleB::class))]
    #[Setup(inputRule: new DiGet(RuleA::class))]
    public function addRule(RuleInterface $inputRule): void
    {
        $this->rules[] = $inputRule;
    }
    
    public function getRules(): array
    {
        return $this->rules;
    }
}
```

```php [RuleInterface.php]
// file: /app/src/Interfaces/RuleInterface.php
namespace App\Interfaces;

interface RuleInterface
{
    // …
}
```

```php [RuleA.php]
// file: /app/src/Rules/RuleA.php
namespace App\Rules;

use App\Interfaces\RuleInterface;

class RuleA implements RuleInterface
{
    // …
}
```

```php [RuleB.php]
// file: /app/src/Rules/RuleB.php
namespace App\Rules;

use App\Interfaces\RuleInterface;

class RuleB implements RuleInterface
{
    // …
}
```

:::


Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\RuleGenerator;
use App\Rules\{RuleA, RuleB};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->build();

$ruleGenerator = $container->get(RuleGenerator::class);
$rules = $ruleGenerator->->getRules();

var_dump($rules[0] instanceof App\Rules\RuleB);
// (bool) true
var_dump($rules[1] instanceof App\Rules\RuleA);
// (bool) true
```

> [!NOTE]
> Последовательность полученных объектов в переменную `$rules` обусловлен очередностью применения PHP атрибута `Kaspi\DiContainer\Attributes\Setup` к методу `RuleGenerator::addRule()`.
