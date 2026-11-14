---
outline: [2, 5]
title: Внедрение зависимости в параметр с типом интерфейс
---
### Внедрение зависимости в параметр с типом интерфейс { #overview }

При некоторых сценариях использования контейнера требуется возможность объявить внедрение зависимости в параметр метода или вызываемого типа[^CallableType] ожидающего объект реализующий определённый интерфейс.

Обеспечить внедрение такого объекта можно связав FQCN[^FQCN] интерфейса с конкретным PHP-классом, при этом контейнер автоматически внедряет нужный объект.

Контейнер позволяет реализовать такое объявление множеством способов, в зависимости от решаемой задачи.

> [!IMPORTANT]
> Получаемый объект PHP-класса из контейнера для внедрения должен реализовывать интерфейс ожидаемый параметром.

#### Глобальное объявление { #global }

Объявить реализацию интерфейса в контейнере конкретным PHP-классом можно глобально, т.е. контейнер будет автоматически внедрять объявленную реализацию интерфейса, если для конкретного параметра метода или вызываемого типа не объявлено внедрение другой реализации требуемого интерфейса.

Для глобального объявления реализации интерфейса необходимо объявить в качестве идентификатора контейнера[^ContainerId] имя интерфейса и его реализацию [через хелпер функцию](../documentation/php-definition/index.md#helper-fn) или **через [атрибут `\Kaspi\DiContainer\Attributes\Service`](../documentation/attribute-definition/service.md)**.

#### Объявление внедрения в конкретный параметр { #param }

Для параметра метода или вызываемого типа представленного конкретным интерфейсом можно объявить внедрение зависимости отличающуюся от глобального объявления реализации интерфейса.

Объявить внедрение зависимости в такой параметр можно через [хелпер функцию](../documentation/php-definition/index.md#helper-fn) или [атрибуты](../documentation/attribute-definition/index.md#attributes).

### Пример объявления реализации интерфейса { #example-global }

Необходимо объявить реализацию определённого интерфейса PHP-классом чтобы внедрение во все типизированные параметры методов было автоматическим, без дополнительного объявления внедрения зависимости в параметры методов.

PHP-классы:

::: code-group
<<< @/_code/cookbook/inject-interface/src/Services/Foo.php
<<< @/_code/cookbook/inject-interface/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/inject-interface/src/Services/Bar.php
<<< @/_code/cookbook/inject-interface/src/Services/Baz.php
:::

Необходимо объявить реализацию интерфейса `\App\Interfaces\QuxInterface` PHP-классом `\App\Services\Bar`, т.е. чтобы контейнер автоматически, без дополнительных объявлений, внедрял зависимость этого класса если параметр метода или вызываемого типа ожидает объект реализующий интерфейс `\App\Interfaces\QuxInterface`.

Обратите внимание что PHP-класс `\App\Services\Baz` так же реализует интерфейс `\App\Interfaces\QuxInterface`, но он не будет внедрен в любой параметр без дополнительного объявления.

#### Хелпер функции { #helper-fn-global } 

Для этой задачи необходимо объявить в [файле конфигураций](../documentation/container-builder/configuration_files.md) PHP-класс `\App\Services\Bar` и в качестве идентификатора контейнера[^ContainerId] указать FQCN[^FQCN] интерфейса.

Ниже рассмотрим варианты объявления реализации интерфейса через различные хелпер функции.

---

##### diAutowire() { #helper-di-autowire-global }

Файл конфигураций `services.php` использует [хелпер функцию `diAutowire()`](../documentation/php-definition/di-autowire.md) чтобы объявить PHP-класс, а в качестве идентификатора контейнера указан интерфейс `\App\Interfaces\QuxInterface`.

Заметьте что здесь приведён простой пример объявления, но при объявлении PHP-класса доступны все методы этой хелпер функции, включая [объявлений внедрения в конструктор класса](../documentation/php-definition/di-autowire.md#bindarguments) и внедрений через [сеттер-методы](../documentation/php-definition/di-autowire.md#di-autowire-setter-method).

::: code-group
<<< @/_code/cookbook/inject-interface/container.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Interfaces\QuxInterface;
use App\Service\Bar;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield QuxInterface::class => diAutowire(Bar::class);//[!code ++]
}
```
:::

<!--@include: _include/inject-interface-result.md-->

---

##### diCallable() { #helper-di-callable-global }

При объявлении реализации интерфейса может потребоваться сложная сборка PHP-класса, поэтому этот процесс можно делегировать любому вызываемому типу[^CallableType].

Файл конфигураций `services.php` использует [хелпер функцию `diCallable()`](../documentation/php-definition/di-callable.md) чтобы объявить PHP-класс через анонимную функцию, а в качестве идентификатора контейнера указан интерфейс `\App\Interfaces\QuxInterface`.

Так же в этом объявлении реализации интерфейса указываем в параметре `$isSingleton` хелпер функции значение `true`, чтобы контейнер получал один и тот же объект, а не создавал его заново.

::: code-group
<<< @/_code/cookbook/inject-interface/container.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Interfaces\QuxInterface;
use App\Service\Bar;

use function Kaspi\DiContainer\diCallable;

return static function (): Generator {
    // Сложная сборка PHP-класса
    $fn = static function (): Bar {//[!code highlight]
        $bar = new Bar();//[!code highlight]
        // [!code highlight]
        // тут возможны дополнительные действия с $bar [!code highlight]
        // [!code highlight]
        return $bar;//[!code highlight]
    };//[!code highlight]

    yield QuxInterface::class => diCallable($fn, isSingleton: true);//[!code ++]
}
```
:::

<!--@include: _include/inject-interface-result.md-->

> [!TIP]
> Аналогичное объявление можно сделать через [хелпер функцию `diFactory()`](../documentation/php-definition/di-factory.md). 

---

##### diGet() { #helper-di-get-global }

В случае если в контейнере уже существует объявление PHP-класса, с указанием всех необходимых внедрений и настроек, то при объявлении реализации интерфейса можно просто указать ссылку на имеющийся идентификатор контейнера этого PHP-класса. 

Файл конфигураций `services.php` использует [хелпер функцию `diGet()`](../documentation/php-definition/di-get.md) чтобы объявить PHP-класс реализацией интерфейса `\App\Interfaces\QuxInterface`.

::: code-group
<<< @/_code/cookbook/inject-interface/container.php
```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php [!code highlight]

use App\Interfaces\QuxInterface;
use App\Service\Bar;

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(Bar::class);//[!code highlight]
    
    // … many lines
    
    yield QuxInterface::class => diGet(Bar::class);//[!code ++]
}
```
:::

<!--@include: _include/inject-interface-result.md-->

---

#### Атрибуты { #attributes-global }

[Атрибут `\Kaspi\DiContainer\Attributes\Service`](../documentation/attribute-definition/service.md) позволяет объявить глобально реализацию интерфейса в контейнере конкретным PHP-классом, т.е. контейнер будет автоматически внедрять объявленную реализацию интерфейса, если для конкретного параметра метода или вызываемого типа не объявлено внедрение другой реализации требуемого интерфейса.

##### Service и FQCN { #service-fqcn }

Необходимо добавить атрибут к интерфейсу `\App\Interfaces\QuxInterface` и указать реализацию через PHP-класс `\App\Services\Bar`.

Параметр конструктора `$id` атрибута принимает строковый параметр, который указывает контейнеру через какой идентификатор контейнера получить объект реализующий этот интерфейс, в этом случае мы можем указать значение `'App\Services\Bar'` или через константу `\App\Services\Bar::class`.

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

::: code-group
<<< @/_code/cookbook/inject-interface/container_attr.php [container.php]
:::

<!--@include: _include/inject-interface-result.md-->

---

##### Service и значение для параметра $id { #service-container-id }

В разделе документации [к атрибуту `\Kaspi\DiContainer\Attributes\Service`](../documentation/attribute-definition/service.md) указано – параметр конструктора `$id` должен быть представлен как идентификатор контейнера. Это означает что если указывается строковое значение и оно является идентификатором контейнера, позволяющее получить объект реализующий объявляемый интерфейс через метод контейнера `get()`, то значение для параметра `$id` допустимо. 

Рассмотрим пример когда реализация интерфейса будет получена через идентификатор контейнера `'services.bar'`, объявленное у PHP-класса `\App\Services\Bar` через [атрибут `\Kaspi\DiContainer\Attributes\Autowire`](../documentation/attribute-definition/autowire.md).

Такое объявление реализации интерфейса может быть полезно если один и тот же PHP-класс необходимо объявить в контейнере с внедрением разных зависимостей и разными идентификаторами контейнера.

::: code-group
```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php

namespace App\Interfaces;

use App\Service\Bar;//[!code ++]
use Kaspi\DiContainer\Attributes\Service;//[!code ++]

#[Service('services.bar')]//[!code ++]
interface QuxInterface
{
// …
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;
use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(id: 'services.bar')]//[!code ++]
final class Bar implements QuxInterface
{
// …
```
:::

::: code-group
<<< @/_code/cookbook/inject-interface/container_attr.php [container.php]
:::

<!--@include: _include/inject-interface-result.md-->


### Пример внедрения в конкретный параметр { #example-arg }

При объявлении внедрения в параметр метода или вызываемого типа ожидающего объект реализующий интерфейс, можно уточнить какую именно реализацию внедрять. При таком объявлении внедряемый PHP-класс может отличаться от [глобально объявленной реализации интерфейса](#global).

PHP-классы:

::: code-group
<<< @/_code/cookbook/inject-interface/src/Services/Foo.php
<<< @/_code/cookbook/inject-interface/src/Services/Qux.php
<<< @/_code/cookbook/inject-interface/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/inject-interface/src/Services/Bar.php
<<< @/_code/cookbook/inject-interface/src/Services/Baz.php
:::

Необходимо объявить реализацию интерфейса `\App\Interfaces\QuxInterface` PHP-классом `\App\Services\Bar`, т.е. чтобы контейнер автоматически, без дополнительных объявлений, внедрял зависимость этого класса если параметр метода или вызываемого типа ожидает объект реализующий интерфейс `\App\Interfaces\QuxInterface`.

Для PHP-класса `\App\Services\Qux` необходимо объявить внедрение зависимости в параметр конструктора `$qux` PHP-класса `\App\Services\Baz`, обратите внимание: внедряемый класса реализуют интерфейс `\App\Interfaces\QuxInterface`.

#### Хелпер функции { #helper-fn-arg }

#### Атрибуты { #attributes-arg }


### 🚧🚧🚧🚧

Необходима конфигурация контейнера для автоматического внедрения параметра `App\Services\Foo::$service`: 


Для этой задачи необходимо чтобы имя интерфейса было указано как идентификатор контейнера, и сконфигурирован PHP-класс реализующий этот интерфейс.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Interfaces\ServiceInterface;


$container = (new DiContainerBuilder())
    ->build();

$object = $container->get(ServiceInterface::class);

var_dump(is_object($object));
// (bool) true
```

## Настройка в конфигурационных файлах определений { #php-definition }

Объявление PHP-классов реализующих конкретный интерфейс в [файле конфигураций](../documentation/container-builder/configuration_files.md).

### `diAutowire()` { #helper-di-autowire }

Для конфигурирования определения используем [хелпер функцию `diAutowire()`](../documentation/php-definition/di-autowire.md).

Конфигурирование:

::: code-group

```php [services.php]
// file: /app/config/services.php
use App\Interfaces\ServiceInterface;
use App\Services\Bar;
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): \Generator {

    yield ServiceInterface::class => diAutowire(Bar::class)
        ->bindArguments(
            diParameter('params.lorem')
        );

};
```

```php [parameters.php]
// file: /app/config/parameters.php

return [
    'params.lorem' => 'Lorem ipsum',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### `diCallable()` { #helper-di-callable }

Для конфигурирования определения используем [хелпер функцию `diCallable()`](../documentation/php-definition/di-callable.md) и анонимную функцию.

Конфигурирование:

::: code-group

```php [services.php]
// file: /app/config/services.php
use App\Interfaces\ServiceInterface;
use App\Services\Bar;
use function Kaspi\DiContainer\{diCallable, diParameter};

return static function (): \Generator {

    $fn = static function (string $paramOne, string $paramTwo) {
        $calculatedParam = $paramOne . $paramTwo;
        
        $bar = new Bar($calculatedParam);
        
        // возможны дополнительные дейаствия с $bar
        
        return $bar;    
    };

    yield ServiceInterface::class => diCallable($fn)
        ->bindArguments(
            diParameter('params.one'),
            diParameter('params.two'),
        );

};
```

```php [parameters.php]
// file: /app/config/parameters.php
return [
    'params.one' => 'foo',
    'params.two' => 'bar',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### `diGet()` { #helper-di-get }

Используем [хелпер функцию `diGet()`](../documentation/php-definition/di-get.md) как указатель на ранее сконфигурированный класс.


Конфигурирование:

::: code-group

```php [services.php]
// file: /app/config/services.php
use function Kaspi\DiContainer\{diAutowire, diParameter, diGet};
use App\Services\{Bar, Foo};
use App\Interfaces\ServiceInterface;

return static function (): \Generator {

    yield diAutowire(Bar::class)
        ->bindArguments(
            diParameter('params.lorem')
        );
        
    // …
   
    yield ServiceInterface::class => diGet(Bar::class);
};
```

```php [parameters.php]
// file: /app/config/parameters.php
return [
    'params.lorem' => 'Lorem ipsum',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters.php')
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

## PHP атрибуты { #php-attributes }

Конфигурирование через PHP атрибуты.

### Service { #attribute-service }

Атрибут `\Kaspi\DiContainer\Attributes\Service` устанавливает в контейнере какой PHP-класс будет выступать реализацией интерфейса `\App\Interfaces\ServiceInterface`.

При таком подходе конфигурирования любая зависимость с типом `\App\Interfaces\ServiceInterface`
будет внедрена с одинаковой реализацией этого интерфейса PHP-классом `\App\Services\Bar`.

::: code-group

```php [ServiceInterface.php]
// file: /app/src/Interfaces/ServiceInterface.php

namespace App\Interfaces;

use App\Services\Bar;// [!code ++]
use Kaspi\DiContainer\Attributes\Service;// [!code ++]

#[Service(Bar::class)]// [!code ++]
interface ServiceInterface
{
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use Kaspi\DiContainer\Attributes\Parameter;

final class Bar implements ServiceInterface
{
    public function __construct(
        #[Parameter('params.lorem')]
        private readonly string $param
    ) {}
}
```

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Interfaces\ServiceInterface;

final class Foo
{
    public function __construct(
        private ServiceInterface $service
    ) {}
    
    // …
    
    public function service(): ServiceInterface
    {
        return $this->service;
    }
}
```

:::

Конфигурирование параметров контейнера:
```php
// file: /app/config/parameters.php
return [
    'params.lorem' => 'Lorem ipsum',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->loadParameters('/app/config/parameters.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### InjectByCallable { #attribute-inject-by-callable }

Конфигурация внедрения зависимости через атрибут `\Kaspi\DiContainer\Attributes\InjectByCallable`.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use App\Helpers\FactoryCreate;// [!code ++]
use Kaspi\DiContainer\Attributes\InjectByCallable;// [!code ++]

final class Foo
{
    public function __construct(
        #[InjectByCallable([FactoryCreate::class, 'makeService'])]// [!code ++]
        private ServiceInterface $service
    ) {}
    
    // …
    
    public function service(): ServiceInterface
    {
        return $this->service;
    }
}
```

```php [FactoryCreate.php]
// file: /app/src/Helpers/FactoryCreate.php

namespace App\Helpers;

use App\Services\Bar;
use Kaspi\DiContainer\Attributes\Parameter;

final class FactoryCreate
{
    public static function makeService(
        #[Parameter('params.one')]
        string $paramOne,
        #[Parameter('params.two')]
        string $paramTwo,
    ): Bar {
        $calculatedParam = $paramOne . $paramTwo;
        
        $bar = new Bar($calculatedParam);
        
        // возможны дополнительные дейаствия с $bar
        
        return $bar;    
    }
}
```

```php [ServiceInterface.php]
// file: /app/src/Interfaces/ServiceInterface.php

namespace App\Interfaces;

interface ServiceInterface
{
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

use App\Interfaces\ServiceInterface;

final class Bar implements ServiceInterface
{
    public function __construct(private readonly string $param) {}
}
```

:::

Конфигурирование параметров контейнера:

```php
// file: /app/config/parameters.php
return [
    'params.one' => 'foo',
    'params.two' => 'bar',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->loadParameters('/app/config/parameters.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

### Inject { #attribute-inject }

Конфигурация внедрения зависимости через атрибут `\Kaspi\DiContainer\Attributes\Inject`.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use Kaspi\DiContainer\Attributes\Inject;// [!code ++]

final class Foo
{
    public function __construct(
        #[Inject(Bar::class)]// [!code ++]
        private ServiceInterface $service
    ) {}
    
    // …
    
    public function service(): ServiceInterface
    {
        return $this->service;
    }
}
```

```php [ServiceInterface.php]
// file: /app/src/Interfaces/ServiceInterface.php

namespace App\Interfaces;

interface ServiceInterface
{
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use Kaspi\DiContainer\Attributes\Parameter;// [!code ++]

final class Bar implements ServiceInterface
{
    public function __construct(
        #[Parameter('params.lorem')]// [!code ++]
        private readonly string $param
    ) {}
}
```

:::

Конфигурирование параметров контейнера:

```php
// file: /app/config/parameters.php
return [
    'params.lorem' => 'Lorem ipsum',
];
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;
use App\Interfaces\ServiceInterface;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->loadParameters('/app/config/parameters.php')
    ->build();

$foo = $container->get(Foo::class);

var_dump($appLogger->service() instanceof ServiceInterface);
// (bool) true
```

<!--@include: ../documentation/_include/term_notes.md-->