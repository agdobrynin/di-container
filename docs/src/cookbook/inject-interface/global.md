---
outline: [2, 5]
title: Глобальное объявление интерфейса
---
### Пример глобального объявления реализации интерфейса { #overview }

Все типизированные параметры метода или вызываемого типа[^CallableType] должны внедрять один и тот же PHP-класс, который заявлен как реализация определённого интерфейса.

PHP-классы:

::: code-group
<<< @/_code/cookbook/inject-interface/src/Services/Foo.php
<<< @/_code/cookbook/inject-interface/src/Interfaces/QuxInterface.php
<<< @/_code/cookbook/inject-interface/src/Services/Bar.php
<<< @/_code/cookbook/inject-interface/src/Services/Baz.php
:::

Необходимо объявить реализацию интерфейса `\App\Interfaces\QuxInterface` PHP-классом `\App\Services\Bar`. Контейнер должен автоматически внедрять объявленную реализацию интерфейса в параметр метод, без дополнительных объявлений.

Обратите внимание что PHP-класс `\App\Services\Baz` так же реализует интерфейс `\App\Interfaces\QuxInterface`, но он не будет внедрен в любой параметр без дополнительного объявления.

#### Хелпер функции { #helper-fn-global }

Для этой задачи необходимо объявить в [файле конфигураций](../../documentation/container-builder/configuration_files.md) PHP-класс `\App\Services\Bar` и в качестве идентификатора контейнера[^ContainerId] указать FQCN[^FQCN] интерфейса.

Ниже рассмотрим варианты объявления реализации интерфейса через различные хелпер функции.

---

##### diAutowire()

Файл конфигураций `services.php` использует [хелпер функцию `diAutowire()`](../../documentation/php-definition/di-autowire.md) чтобы объявить PHP-класс, а в качестве идентификатора контейнера указан интерфейс `\App\Interfaces\QuxInterface`.

Заметьте что здесь приведён простой пример объявления, но при объявлении PHP-класса доступны все методы этой хелпер функции, включая [объявлений внедрения в конструктор класса](../../documentation/php-definition/di-autowire.md#bindarguments) и внедрений через [сеттер-методы](../../documentation/php-definition/di-autowire.md#di-autowire-setter-method).

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
};
```
:::

<!--@include: _include/inject-interface-result.md-->

---

##### diCallable()

При объявлении реализации интерфейса может потребоваться сложная сборка PHP-класса, поэтому этот процесс можно делегировать любому вызываемому типу[^CallableType].

Файл конфигураций `services.php` использует [хелпер функцию `diCallable()`](../../documentation/php-definition/di-callable.md) чтобы объявить PHP-класс через анонимную функцию, а в качестве идентификатора контейнера указан интерфейс `\App\Interfaces\QuxInterface`.

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
};
```
:::

<!--@include: _include/inject-interface-result.md-->

> [!TIP]
> Аналогичное объявление можно сделать через [хелпер функцию `diFactory()`](../../documentation/php-definition/di-factory.md).

---

##### diGet()

В случае если в контейнере уже существует объявление PHP-класса, с указанием всех необходимых внедрений и настроек, то при объявлении реализации интерфейса можно просто указать ссылку на имеющийся идентификатор контейнера этого PHP-класса.

Файл конфигураций `services.php` использует [хелпер функцию `diGet()`](../../documentation/php-definition/di-get.md) чтобы объявить PHP-класс реализацией интерфейса `\App\Interfaces\QuxInterface`.

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
};
```
:::

<!--@include: _include/inject-interface-result.md-->

---

#### Атрибуты { #attributes-global }

[Атрибут `\Kaspi\DiContainer\Attributes\Service`](../../documentation/attribute-definition/service.md) позволяет объявить глобально реализацию интерфейса в контейнере конкретным PHP-классом, т.е. контейнер будет автоматически внедрять объявленную реализацию интерфейса, если для конкретного параметра метода или вызываемого типа не объявлено внедрение другой реализации требуемого интерфейса.

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

В разделе документации [к атрибуту `\Kaspi\DiContainer\Attributes\Service`](../../documentation/attribute-definition/service.md) указано – параметр конструктора `$id` должен быть представлен как идентификатор контейнера. Это означает что если указывается строковое значение и оно является идентификатором контейнера, позволяющее получить объект реализующий объявляемый интерфейс через метод контейнера `get()`, то значение для параметра `$id` допустимо.

Рассмотрим пример когда реализация интерфейса будет получена через идентификатор контейнера `'services.bar'`, объявленное у PHP-класса `\App\Services\Bar` через [атрибут `\Kaspi\DiContainer\Attributes\Autowire`](../../documentation/attribute-definition/autowire.md).

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

<!--@include: ../../documentation/_include/term_notes.md-->
