---
outline: [2, 5]
title: Declaration of an insertion into a specific parameter
---
### Example of implementation for a specific parameter { #overview }

При объявлении внедрения в параметр метода или вызываемого типа[^CallableType] ожидающего объект реализующий определённый интерфейс, можно уточнить какую именно реализацию внедрять. При таком объявлении внедряемый PHP-класс может отличаться от [глобально объявленной реализации интерфейса](index.md#global).

### PHP-классы { #php-classes }

::: code-group
<<< @/_code/cookbook/inject-interface/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/inject-interface/src/Services/Foo.php
<<< @/_code/cookbook/inject-interface/src/Services/Qux.php
<<< @/_code/cookbook/inject-interface/src/Services/Bar.php
<<< @/_code/cookbook/inject-interface/src/Services/Baz.php
:::

Необходимо объявить реализацию интерфейса `\App\Interfaces\QuxInterface` PHP-классом `\App\Services\Bar`, т.е. чтобы контейнер автоматически внедрял в параметр метода на основании конфигурации.

Для PHP-класса `\App\Services\Qux` необходимо объявить внедрение зависимости в параметр конструктора `$qux` PHP-класса `\App\Services\Baz`, обратите внимание: внедряемый класса реализуют интерфейс `\App\Interfaces\QuxInterface`.

#### Хелпер функции { #helper-fn-arg }

В файле конфигураций `services.php` указана основная реализация интерфейса `\App\Interfaces\QuxInterface` PHP-классом `\App\Services\Bar`.

::: code-group
<<< @/_code/cookbook/inject-interface/services.php
:::

Это означает что в любой параметр с типом `\App\Interfaces\QuxInterface` будет внедрён объект PHP-класса `\App\Service\Bar`.

##### diAutowire()

Файл конфигураций `services_qux.php` использует [хелпер функцию `diAutowire()`](../../documentation/php-definition/di-autowire.md) чтобы объявить внедрение в параметр конструктора `$qux` PHP-класса `\App\Services\Qux`.

> [!NOTE]
> Здесь приведён простой пример внедрения через хелпер функцию. При объявлении внедрения PHP-класса через хелпер функцию доступны все методы этой хелпер функции, включая [объявлений внедрения в конструктор класса](../../documentation/php-definition/di-autowire.md#bindarguments) и внедрений через [сеттер-методы](../../documentation/php-definition/di-autowire.md#di-autowire-setter-method).

::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\diAutowire;//[!code ++]

return static function (): Generator {
    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diAutowire(Baz::class)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->

---

##### diCallable()

В некоторых случая когда требуется сложная инициализация PHP-класса процесс создания такого объекта можно делегировать любому вызываемому типу[^CallableType].

Файл конфигураций `services_qux.php` использует [хелпер функцию `diCallable()`](../../documentation/php-definition/di-callable.md) чтобы объявить PHP-класс через анонимную функцию.


::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\{diAutowire, diCallable};//[!code ++]

return static function (): Generator {
    // Сложная сборка PHP-класса
    $fn = static function (): Baz {
        $baz = new Baz();
        // тут возможны дополнительные действия с $baz
        return $baz;
    };

    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diCallable($fn)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->


> [!TIP]
> Аналогичное объявление можно сделать через [хелпер функцию `diFactory()`](../../documentation/php-definition/di-factory.md).

---

##### diGet()

В случае если в контейнере уже существует объявление PHP-класса, с указанием всех необходимых внедрений и настроек и он доступен для метода контейнера `get()`, то при объявлении реализации интерфейса можно просто указать ссылку на имеющийся идентификатор контейнера этого PHP-класса.

Файл конфигураций `services_qux.php` использует [хелпер функцию `diGet()`](../../documentation/php-definition/di-get.md) для объявления внедрения в параметр.


::: code-group
<<< @/_code/cookbook/inject-interface/container_build.php
```php [services_qux.php]
<?php
declare(strict_types=1);

// file: /app/config/services_qux.php [!code highlight]

use App\Services\{Qux, Baz};

use function Kaspi\DiContainer\{diAutowire, diGet};//[!code ++]

return static function (): Generator {
    yield diAutowire(Baz::class);//[!code highlight]

    // … many lines

    yield diAutowire(Qux::class)
        ->bindArguments(//[!code ++]
            qux: diGet(Baz::class)//[!code ++]
        );//[!code ++]
};
```
<<< @/_code/cookbook/inject-interface/services.php
:::

<!--@include: _include/inject-interface-arg-result.md-->

#### Атрибуты { #attributes-arg }

Реализация интерфейса `\App\Interfaces\QuxInterface` объявлена PHP-классом `\App\Services\Bar` глобально через [атрибут `\Kaspi\DiContainer\Attributes\Service`](../../documentation/attribute-definition/service.md).

::: code-group
```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php

namespace App\Interfaces;

use App\Service\Bar;//[!code ++]
use Kaspi\DiContainer\Attributes\Service;//[!code ++]

#[Service(Bar::class)]//[!code ++]
interface QuxInterface
{
    // …
}
```
:::

Это означает что в любой параметр с типом `\App\Interfaces\QuxInterface` будет внедрён объект PHP-класса `\App\Service\Bar`.

---

##### Autowire

Параметр конструктора `$qux` PHP-класса `\App\Services\Qux` представлен типом `\App\Interfaces\QuxInterface`, необходимо объявить внедрение в этот параметр другой реализации интерфейса, а именно PHP-класс `\App\Services\Baz`. Объявить внедрение можно через [атрибут `\Kaspi\DiContainer\Attributes\Autowire`](../../documentation/attribute-definition/autowire.md).

> [!NOTE]
> Здесь приведён простой пример использования атрибута. При объявлении внедрения PHP-класса через атрибут доступны все возможности атрибута, включая [объявления аргументов конструктора](../../documentation/attribute-definition/autowire.md#arguments) и внедрений через [сеттер-методы](../../documentation/attribute-definition/autowire.md#setups).

::: code-group
```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

final class Qux
{
    public function __construct(
        #[Autowire(Bar::class)]//[!code ++]
        public readonly QuxInterface $qux
    ) {}
}
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_build_attr.php [container_build.php]
:::

<!--@include: _include/inject-interface-arg-result.md-->

---

##### InjectByCallable

При объявлении реализации интерфейса может потребоваться сложная сборка PHP-класса, поэтому этот процесс можно делегировать любому вызываемому типу[^CallableType].

В этом примере в качестве вызываемого типа выступает статический метод PHP-класса – `\App\Helpers\FactoryCreate::makeService()`, а объявить внедрение можно через [атрибут `\Kaspi\DiContainer\Attributes\InjectByCallable`](../../documentation/attribute-definition/inject-by-callable.md).

::: code-group
```php [FactoryCreate.php]
<?php//[!code ++]
declare(strict_types=1);//[!code ++]

// file: /app/src/Helpers/FactoryCreate.php [!code highlight]

namespace App\Helpers;//[!code ++]

use App\Services\Baz;//[!code ++]

final class FactoryCreate//[!code ++]
{//[!code ++]
    // Сложная сборка PHP-класса//[!code ++]
    public static function makeService(): Baz//[!code ++]
    {//[!code ++]
        $baz = new Baz();//[!code ++]
        // тут возможны дополнительные действия с $baz //[!code ++]
        return $baz;//[!code ++]
    }//[!code ++]
}//[!code ++]
```
```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\InjectByCallable;//[!code ++]
use App\Helpers\FactoryCreate;//[!code ++]

final class Qux
{
    public function __construct(
        #[InjectByCallable([//[!code ++]
            FactoryCreate::class,//[!code ++]
            'makeService',//[!code ++]
        )]//[!code ++]
        public readonly QuxInterface $qux
    ) {}
}
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_build_attr.php [container_build.php]
:::

<!--@include: _include/inject-interface-arg-result.md-->

---

##### Inject

В случае если в контейнере уже объявлен PHP-класс, с указанием всех необходимых внедрений и настроек, и он доступен для метода контейнера `get()`, то можно использовать [атрибут `\Kaspi\DiContainer\Attributes\Inject`](../../documentation/attribute-definition/inject.md) для объявления внедрения в параметр.

::: code-group
```php [Qux.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Qux.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Inject;//[!code ++]

final class Qux
{
    public function __construct(
        #[Inject(Bar::class)]//[!code ++]
        public readonly QuxInterface $qux
    ) {}
}
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_build_attr.php [container_build.php]
:::

<!--@include: _include/inject-interface-arg-result.md-->


<!--@include: ../../documentation/_include/term_notes.md-->
