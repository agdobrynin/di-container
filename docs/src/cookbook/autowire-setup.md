---
outline: [2, 4]
---
# Внедрение зависимостей через сеттер-методы

## Обзор

Внедрение зависимостей в PHP класс может происходить так же через **сеттер-методы**.

При объявлении PHP класса используются методы [`setup()`](../documentation/php-definition/di-autowire.md#setup), [`setupImmutable()`](../documentation/php-definition/di-autowire.md#setupimmutable) хелпер функции `diAutowire()` или атрибуты [`\Kaspi\DiContainer\Attributes\Setup`](../documentation/attribute-definition/setup.md), [`\Kaspi\DiContainer\Attributes\SetupImmutable`](../documentation/attribute-definition/setup-immutable.md).

## Объявление PHP классов в файлах конфигураций { #php-definition }

Внедрение зависимости через сеттер-метод в [файле конфигураций](../documentation/container-builder/configuration_files.md).

### diAutowire::setup()

Внедрение зависимости через мутабельный сеттер-метод [^SetterMethodMutable] `\App\Services\FooLogger::setLogger()`.

::: code-group

<<< @/_code/scripts/cookbook/autowire-setup/container_build.php

```php [services.php]
<?php
// file: /app/config/services/services.php
use App\Services\{FooLogger, FileLogger};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function(): \Generator {
    yield diAutowire(FooLogger::class)
        ->setup(// [!code ++]
            'setLogger',// [!code ++]
            [diGet(FileLogger::class)]// [!code ++]
        );// [!code ++]
};
```

<<< @/_code/scripts/cookbook/autowire-setup/service_logger.php

<<< @/_code/scripts/cookbook/autowire-setup/params_logger.php

<<< @/_code/scripts/cookbook/autowire-setup/src/Services/FooLogger.php

<<< @/_code/scripts/cookbook/autowire-setup/src/Services/FileLogger.php

:::

### diAutowire::setupImmutable()

Внедрение зависимости через иммутабельный сеттер-метод [^SetterMethodImmutable] `\App\Services\FooLogger::withLogger()`.

::: code-group

<<< @/_code/scripts/cookbook/autowire-setup/container_build.php

```php [services.php]
<?php
// file: /app/config/services/services.php
use App\Services\{FooLogger, FileLogger};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function(): \Generator {
    yield diAutowire(FooLogger::class)
        ->setupImmutable(// [!code ++]
            'withLogger',// [!code ++]
            [diGet(FileLogger::class)]// [!code ++]
        );// [!code ++]
};
```
<<< @/_code/scripts/cookbook/autowire-setup/service_logger.php

<<< @/_code/scripts/cookbook/autowire-setup/service_logger.php

<<< @/_code/scripts/cookbook/autowire-setup/params_logger.php

<<< @/_code/scripts/cookbook/autowire-setup/src/Services/FooLogger.php

<<< @/_code/scripts/cookbook/autowire-setup/src/Services/FileLogger.php

:::

## PHP атрибуты { #php-attributes }

Внедрение зависимостей через сеттер-методы атрибутами `\Kaspi\DiContainer\Attributes\Setup` и `\Kaspi\DiContainer\Attributes\SetupImmutable`.

### Setup { #attribute-setup }

Атрибут `\Kaspi\DiContainer\Attributes\Setup` применяется к мутабельному сеттер-методу[^SetterMethodMutable] `\App\Services\FooLogger::setLogger()`.

## 🚧🚧🚧🚧

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

Атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable` применяется к иммутабельному сеттер-методу[^SetterMethodImmutable] `\App\Services\FooLogger::withLogger()`.

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

Внедрение нескольких зависимостей через один мутабельный сеттер-метод `\App\Services\RuleGenerator::addRule()`.

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