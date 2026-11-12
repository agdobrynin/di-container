---
outline: [2, 4]
title: Внедрение зависимостей через сеттер-методы
---
### Внедрение зависимостей через сеттер-методы { #title }

Внедрение зависимостей в PHP-класс может происходить через **сеттер-методы**.

Объявить внедрение зависимостей через сеттер-методы можно через методы [`setup()`](../documentation/php-definition/di-autowire.md#setup), [`setupImmutable()`](../documentation/php-definition/di-autowire.md#setupimmutable) предоставляемые хелпер функцией `diAutowire()` или атрибуты [`\Kaspi\DiContainer\Attributes\Setup`](../documentation/attribute-definition/setup.md), [`\Kaspi\DiContainer\Attributes\SetupImmutable`](../documentation/attribute-definition/setup-immutable.md).

### Мутабельный сеттер-метод[^SetterMethodMutable] { #example-setup }

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

    public function setQux(QuxInterface $qux): void//[!code warning]
    {//[!code warning]
        $this->qux = $qux;//[!code warning]
    }//[!code warning]

    public function getQux(): ?QuxInterface
    {
        return $this->qux ?? null;
    }
}
```
<<< @/_code/cookbook/autowire-setup/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/autowire-setup/src/Services/Bar.php
:::

Необходимо объявить внедрение в PHP-класа `\App\Services\Bar` через мутабельный сеттер-метод `\App\Services\Foo::setQux()`.

Параметр `$qux` метода `\App\Services\Foo::setQux()` ожидает объект реализующий интерфейс `\App\Interfaces\QuxInterface`. Контейнер предоставляет [множество способов объявления](inject-interface.md) внедрения PHP-класса в параметр, для этого примера будет использована [хелпер функция `diGet()`](../documentation/php-definition/di-get.md) или [атрибут `\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) в зависимости от способа объявления внедрения зависимости.

#### Объявление в файлах конфигураций { #example-stup-php-definition }

В файле конфигураций `services.php` объявим PHP-класс `\App\Services\Foo` и объявление внедрения через сеттер-метод `'setQux'`.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->getQux());
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Service\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->setup(//[!code ++]
            'setQux',//[!code ++]
            [//[!code ++]
                'qux' => diGet(Bar::class),//[!code ++]
            ],//[!code ++]
        );//[!code ++]
};
```
::: 

<!--@include: _include/autowire-setup-result.md-->

#### Объявление через атрибут { #example-stup-attribute }

Объявим внедрение PHP-класса `\App\Services\Bar` через атрибут `\Kaspi\DiContainer\Attributes\Setup`, указав его для сеттер-метода `\App\Services\Foo::setQux()`. Для объявления внедрения в параметр сеттер-метода используем атрибут `\Kaspi\DiContainer\Attributes\Inject`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]
use Kaspi\DiContainer\Attributes\Setup;//[!code ++]

final class Foo
{
    private QuxInterface $qux;

    public function setQux(QuxInterface $qux): void//[!code --]
    #[Setup]//[!code ++]
    public function setQux(//[!code ++]
        #[Inject(Bar::class)]//[!code ++]
        QuxInterface $qux//[!code ++]
    ): void//[!code ++]
    {
// …
```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->getQux());
```
:::

<!--@include: _include/autowire-setup-result.md-->

### Иммутабельный сеттер-метод[^SetterMethodImmutable]

PHP-классы:

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Baz
{
    private QuxInterface $qux;

    public function withQux(QuxInterface $qux): self//[!code warning]
    {//[!code warning]
        $new = clone $this;//[!code warning]
        $new->qux = $qux;//[!code warning]
//[!code warning]

        return $new;//[!code warning]
    }//[!code warning]

    public function getQux(): ?QuxInterface
    {
        return $this->qux ?? null;
    }
}
```
<<< @/_code/cookbook/autowire-setup/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/autowire-setup/src/Services/Bar.php
:::

Необходимо объявить внедрение в PHP-класа `\App\Services\Bar` через иммутабельный сеттер-метод `\App\Services\Baz::withQux()`. Обратите внимание, сеттер-метод возвращает новый объект класса `\App\Services\Baz`.

Параметр `$qux` метода `\App\Services\Baz::withQux()` ожидает объект реализующий интерфейс `\App\Interfaces\QuxInterface`. Контейнер предоставляет [множество способов объявления](inject-interface.md) внедрения PHP-класса в параметр, для этого примера будет использована [хелпер функция `diGet()`](../documentation/php-definition/di-get.md) или [атрибут `\Kaspi\DiContainer\Attributes\Inject`](../documentation/attribute-definition/inject.md) в зависимости от способа объявления внедрения зависимости.


#### Объявление в файлах конфигураций { #example-stupimmutable-php-definition}

В файле конфигураций `services.php` объявим PHP-класс `\App\Services\Baz` и объявление внедрения через сеттер-метод `'withQux'`.

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Baz;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')//[!code ++]
    ->build();

$baz = $container->get(Baz::class);
var_dump($baz->getQux());
```
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Service\{Baz, Bar};

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Baz::class)
        ->setupImmutable(//[!code ++]
            'withQux',//[!code ++]
            [//[!code ++]
                'qux' => diGet(Bar::class),//[!code ++]
            ],//[!code ++]
        );//[!code ++]
};
```
:::

<!--@include: _include/autowire-setup-result.md-->

#### Объявление через атрибут { #example-stupimmutable-attribute }

Объявим внедрение PHP-класса `\App\Services\Bar` через атрибут `\Kaspi\DiContainer\Attributes\SetupImmutable`, указав его для сеттер-метода `\App\Services\Baz::withQux()`. Для объявления внедрения в параметр сеттер-метода используем атрибут `\Kaspi\DiContainer\Attributes\Inject`.

::: code-group
```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]
use Kaspi\DiContainer\Attributes\SetupImmutable;//[!code ++]

final class Baz
{
    private QuxInterface $qux;

    public function withQux(QuxInterface $qux): self//[!code --]
    #[SetupImmutable]//[!code ++]
    public function withQux(//[!code ++]
        #[Inject(Bar::class)]//[!code ++]
        QuxInterface $qux//[!code ++]
    ): void//[!code ++]
    {
// …
```
:::

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Baz;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$baz = $container->get(Baz::class);
var_dump($baz->getQux());
```
:::

<!--@include: _include/autowire-setup-result.md-->

### Внедрение нескольких зависимостей { #attribute-setup-multiple }

Рассмотрим на примере внедрения нескольких зависимостей через один мутабельный[^SetterMethodMutable] сеттер-метод `\App\Services\Rules::addRule()`.

Для объявления внедрения зависимостей используем атрибут `\Kaspi\DiContainer\Attributes\Setup`.

Параметр `$inputRule` сеттер-метода ожидает объект реализующий интерфейс `\App\Interfaces\RuleInterface`. Контейнер предоставляет [множество способов объявления](inject-interface.md) внедрения PHP-класса в параметр, для этого примера будет использована PHP-класс `\Kaspi\DiContainer\DiDefinition\DiDefinitionGet`.

<!--@include: ../documentation/attribute-definition/_include/to_arguments.md-->

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Rules;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')//[!code ++]
    ->build();

$rules = $container->get(Rules::class);

foreach($rules->getRules() as $rule) {
    var_dump($rule);
    print PHP_EOL;
}
```

```php [Rules.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Rules.php [!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Setup;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;//[!code ++]
use App\Rules\{RuleA, RuleB};
use App\Interfaces\RuleInterface;

class Rules
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
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/RuleInterface.php [!code highlight]

namespace App\Interfaces;

interface RuleInterface
{
    // …
}
```

```php [RuleA.php]
<?php
declare(strict_types=1);

// file: /app/src/Rules/RuleA.php [!code highlight]

namespace App\Rules;

use App\Interfaces\RuleInterface;

class RuleA implements RuleInterface
{
    // …
}
```

```php [RuleB.php]
<?php
declare(strict_types=1);

// file: /app/src/Rules/RuleB.php [!code highlight]

namespace App\Rules;

use App\Interfaces\RuleInterface;

class RuleB implements RuleInterface
{
    // …
}
```

:::

Выполните в терминале команду:

```shell
php /app/src/container.php
```

Появится сообщение вида:

```console
object(App\Rules\RuleB)#2 (0) {
}

object(App\Rules\RuleA)#2 (0) {
}
```
Очерёдность внедренных объектов через сеттер-метод обусловлена порядком применения атрибута `\Kaspi\DiContainer\Attributes\Setup` к методу `\App\Services\RuleGenerator::addRule()`.

<!--@include: ../documentation/_include/term_notes.md-->