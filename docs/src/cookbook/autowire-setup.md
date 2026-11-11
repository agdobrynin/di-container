---
outline: [2, 4]
title: Внедрение зависимостей через сеттер-методы
---
### Внедрение зависимостей через сеттер-методы { #title }

Внедрение зависимостей в PHP-класс может происходить через **сеттер-методы**.

Объявить внедрение зависимостей через сеттер-методы можно через методы [`setup()`](../documentation/php-definition/di-autowire.md#setup), [`setupImmutable()`](../documentation/php-definition/di-autowire.md#setupimmutable) предоставляемые хелпер функцией `diAutowire()` или атрибуты [`\Kaspi\DiContainer\Attributes\Setup`](../documentation/attribute-definition/setup.md), [`\Kaspi\DiContainer\Attributes\SetupImmutable`](../documentation/attribute-definition/setup-immutable.md).

### Внедрение зависимости через мутабельный сеттер-метод { #example-setup }

PHP-классы:

::: code-group
```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo
{
    private QuxInterface $qux;

    public function setQux(QuxInterface $qux): void
    {
        $this->qux = $qux;
    }

    public function getQux(): ?QuxInterface
    {
        return $this->qux ?? null;
    }
}
```
<<< @/_code/cookbook/autowire-setup/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/autowire-setup/src/Services/Bar.php
:::

Необходимо объявить внедрение в PHP-класа `\App\Services\Bar` через мутабельный сеттер-метод[^SetterMethodMutable] `\App\Services\Foo::setQux()`.

Параметр `$qux` метода `\App\Services\Foo::setQux()` ожидает объект реализующий интерфейс `App\Interfaces\QuxInterface`. Контейнер предоставляет множество способов объявления [внедрения PHP-класса в параметр метода ожидающего интерфейс](inject-interface.md), для этого примера будет использовать [хелпер функцию `diGet()`](../documentation/php-definition/di-get.md) или [атрибут `\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) в зависимости от способа объявления внедрения зависимости.

🚧🚧🚧🚧🚧

#### Объявление в файлах конфигураций { #example-stup-php-definition }

В файле конфигураций `services.php` объявим PHP-класс `\App\Services\Foo`, через метод `setup()`, предоставляемый хелпер функцией `diAutowire()`, объявим внедрение зависимости PHP-класса `\App\Services\Bar`.

::: code-group
```php
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Service\Foo;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->setup();
};
```
::: 

#### Объявление через атрибут { #example-stup-attribute }




## Объявление PHP-классов в файлах конфигураций { #php-definition }

Внедрение зависимости через сеттер-метод в [файле конфигураций](../documentation/container-builder/configuration_files.md).

### diAutowire::setup()

Внедрение зависимости через мутабельный сеттер-метод [^SetterMethodMutable] `\App\Services\FooLogger::setLogger()`.

::: code-group

<<< @/_code/cookbook/autowire-setup/container_build.php

<<< @/_code/cookbook/autowire-setup/params_logger.php

```php [services.php]
<?php
declare(strict_types=1);

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

<<< @/_code/cookbook/autowire-setup/service_logger.php

```php [FooLogger.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooLogger.php
namespace App\Services;

use Psr\Log\LoggerInterface;

final class FooLogger
{
    private LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }
}
```


:::

### diAutowire::setupImmutable()

Внедрение зависимости через иммутабельный сеттер-метод [^SetterMethodImmutable] `\App\Services\FooLogger::withLogger()`.

::: code-group

<<< @/_code/cookbook/autowire-setup/container_build.php

<<< @/_code/cookbook/autowire-setup/params_logger.php

```php [services.php]
<?php
declare(strict_types=1);

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

<<< @/_code/cookbook/autowire-setup/service_logger.php

```php [FooLogger.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooLogger.php
namespace App\Services;

use Psr\Log\LoggerInterface;

final class FooLogger
{
    private LoggerInterface $logger;

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

<<< @/_code/cookbook/autowire-setup/FileLogger.php

:::

## PHP атрибуты { #php-attributes }

Внедрение зависимостей через сеттер-методы атрибутами `\Kaspi\DiContainer\Attributes\Setup` и `\Kaspi\DiContainer\Attributes\SetupImmutable`.

### Setup { #attribute-setup }

Атрибут `\Kaspi\DiContainer\Attributes\Setup` применяется к мутабельному сеттер-методу[^SetterMethodMutable] `\App\Services\FooLogger::setLogger()`.

::: code-group

<<< @/_code/cookbook/autowire-setup/container_build_attr.php

<<< @/_code/cookbook/autowire-setup/params_logger.php

```php [FooLogger.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooLogger.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;// [!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;// [!code ++]
use Psr\Log\LoggerInterface;

final class FooLogger
{
    private LoggerInterface $logger;

    #[Setup(// [!code ++]
        new DiGet(FileLogger::class)// [!code ++]
    )]// [!code ++]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }
}
```

<<< @/_code/cookbook/autowire-setup/FileLoggerAttr.php [FileLogger.php]

:::

### SetupImmutable { #attribute-setup-immutable }

Атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable` применяется к иммутабельному сеттер-методу[^SetterMethodImmutable] `\App\Services\FooLogger::withLogger()`.

::: code-group

<<< @/_code/cookbook/autowire-setup/container_build_attr.php

<<< @/_code/cookbook/autowire-setup/params_logger.php

```php [FooLogger.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/FooLogger.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\SetupImmutable;// [!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;// [!code ++]
use Psr\Log\LoggerInterface;

final class FooLogger
{
    private LoggerInterface $logger;

    #[SetupImmutable(// [!code ++]
        new DiGet(FileLogger::class)// [!code ++]
    )]// [!code ++]
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

<<< @/_code/cookbook/autowire-setup/FileLoggerAttr.php [FileLogger.php]

:::

### Внедрение нескольких параметров { #attribute-setup-multiple }

Внедрение нескольких зависимостей через один мутабельный сеттер-метод `\App\Services\RuleGenerator::addRule()`.

::: code-group

```php
<?php
declare(strict_types=1);

// file: /app/src/container_build.php
require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

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


> [!NOTE]
> Последовательность полученных объектов в свойство `\App\Services\RuleGenerator::$rules` обусловлен очередностью применения PHP атрибута `\Kaspi\DiContainer\Attributes\Setup` к методу `\App\Services\RuleGenerator::addRule()`.

<!--@include: ../documentation/_include/term_notes.md-->