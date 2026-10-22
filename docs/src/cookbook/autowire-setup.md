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

## Объявление PHP классов в файлах конфигураций { #php-definition }

Внедрение зависимости через сеттер-метод в [файле конфигураций](../documentation/container-builder/configuration_files.md).

### diAutowire::setup()

Внедрение зависимости через мутабельный сеттер-метод [^SetterMethodMutable].

Объявление [PHP классов](#src-class):

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

```php {7-8}
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

Внедрение зависимости через иммутабельный сеттер-метод [^SetterMethodImmutable].

Объявление [PHP классов](#src-class):

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

```php {7-8}
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

Внедрение зависимостей через сеттер-методы атрибутами `\Kaspi\DiContainer\Attributes\Setup` и `\Kaspi\DiContainer\Attributes\SetupImmutable`.

### Setup { #attribute-setup }

Атрибут `\Kaspi\DiContainer\Attributes\Setup` применяется к мутабельному сеттер-методу[^SetterMethodMutable].

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\Setup;//[!code ++]

class Foo
{
    private LoggerInterface $logger;

    #[Setup]//[!code ++]
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
use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]

class FileLogger implements LoggerInterface
{
    public function __construct(
        #[Parameter('files.logger_file')]//[!code ++]
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

```php {7}
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

Атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable` применяется к иммутабельному сеттер-методу[^SetterMethodImmutable].

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\SetupImmutable;//[!code ++]

class Foo
{
    private LoggerInterface $logger;

    #[SetupImmutable]//[!code ++]
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
use Kaspi\DiContainer\Attributes\Parameter;//[!code ++]

class FileLogger implements LoggerInterface
{
    public function __construct(
        #[Parameter('files.logger_file')]//[!code ++]
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

```php {7}
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

### Внедрение нескольких параметров { #attribute-setup-multiple }

Внедрение нескольких зависимостей через один сеттер-метод с использованием [атрибута `\Kaspi\DiContainer\Attributes\Setup`](../documentation/attribute-definition/setup.md).

::: code-group

```php [RuleGenerator.php]
// file: /app/src/Services/RuleGenerator.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;//[!code ++]
use App\Rules\{RuleA, RuleB};
use App\Interfaces\RuleInterface;

class RuleGenerator
{

    private array $rules = [];
    
    // …
    
    #[Setup(//[!code ++]
        new DiGet(RuleB::class)//[!code ++]
    )]//[!code ++]
    #[Setup(//[!code ++]
        new DiGet(RuleA::class)//[!code ++]
    )]//[!code ++]
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

<!--@include: ../documentation/_include/term_notes.md-->