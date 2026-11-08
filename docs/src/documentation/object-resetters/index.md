# Сброс состояния объектов в долго-живущих процессах { #title }

## Обзор { #overview }

Некоторые объекты полученные через метод контейнера `get()` в зависимости от конфигурации могут возвращать [один и тот же объект](../container-config/index.md#is-singleton-service-default), т.е. сохраняя своё состояние в рамках всех вызовов контейнера. Такие объекты могут требовать сброса своего состояния в долгоживущих процессах.

Пример класса который может «накапливать данные»:
```php
<?php
declare(strict_types=1);

namespace  App\Services;

final class FooLogger
{
    private array $logs = [];

    // other methods

    public function log(string $where, string $message): void
    {
        $this->logs[] = [
            'where' => $where,
            'message' => $message,
        ];
    }
    
    public function getLogs(): array
    {
        return $this->logs;
    }
}
```

Например, приложению необходимо чтобы объект PHP-класса `\App\Services\FooLogger` сохранял своё состояние между компонентами системы, т.е. получать один и тот же объект при внедрении зависимости или получении через метод контейнера `get()`.

При объявлении такого PHP-класса в контейнере необходимо установить значение `true` в параметр `$isSingleton` в [хелпер функции `diAutowire()`](../php-definition/di-autowire.md) или [атрибута `\Kaspi\DiContainer\Attributes\Aitowire`](../attribute-definition/autowire.md).

Пример объявления PHP-класса через атрибут:

```php
<?php
declare(strict_types=1);

namespace  App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(isSingleton: true)]//[!code ++]
final class FooLogger
{
//…
```

Приложение работающее по схеме «event loop»:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code ++]
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container
);

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);
}
```

Получая объект через `$container->get(App\Services\FooLogger::class)` внутри приложения будет получен один и тот-же объект. Разные компоненты приложения могут внедрять зависимость и вызывать метод PHP-класса `\App\Services\FooLogger::log()`, что приведет к «накоплению данных» в свойстве класса `\App\Services\FooLogger::$logs`.

**Чтобы избежать утечек памяти, нужно использовать механизм сброса состояния объекта `\App\Services\FooLogger`.**

Контейнер предоставляет готовый механизм сброса состояния объектов контейнера через PHP-класс `\Kaspi\DiContainer\ObjectResetters`.

Конфигурация для сброса состояния объекта представляется парой ключ-значение, где ключ это идентификатор контейнера[^ContainerId], а значение, представленное типами `string`, `false` или `callable`, указывает [как необходимо выполнить сброса состояния объекта](#resseter-types).

Выберем в качестве сброса состояния объекта выберем реализацию публичного метода в этом же классе `\App\Services\FooLogger::doReset()` и объявления в параметре `$resetter` атрибута `\Kaspi\DiContainer\Attributes\Autowire`:

```php
<?php
declare(strict_types=1);

namespace  App\Services;

use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]

#[Autowire(isSingleton: true)]//[!code --]
#[Autowire(//[!code ++]
    isSingleton: true,//[!code ++]
    resetter: 'doReset',//[!code ++]
)]//[!code ++]
final class FooLogger
{
//…

    public function doReset(): void//[!code ++]
    {//[!code ++]
        $this->logs = [];//[!code ++]
    }//[!code ++]
```

Теперь добавим в наше приложение работающее по схеме «event loop», использование PHP-класса `\Kaspi\DiContainer\ObjectResetters`:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\ObjectResetters;//[!code ++]
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container
);

$resetters = $container->get(ObjectResetters::class);//[!code ++]

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);
    $resetters->reset();//[!code ++]
}
```

Теперь PHP-класс `\App\Services\FooLogger` будет сбрасывать своё состояния при вызове `$resetters->reset()`.

## Способы конфигурирования сброса состояния объектов { #config }

Объявить PHP-класс `\Kaspi\DiContainer\ObjectResetters` и его конфигурацию для сброса состояния объектов можно следующими способами:

- [Конфигурация из определений контейнера](definition_config.md).
- [Конфигурация отдельных значений сброса состояния объектов](manual_config.md).

> [!TIP]
> Для случаев когда невозможно изменить код PHP-класса, чтобы реализовать метод сброса состояния объекта, например PHP-класс из пакета стороннего производителя, можно [воспользоваться механизмом рефлексии объектов](reset_vendor_class.md).

> [!NOTE]
> Разработчик может реализовать собственный PHP-класс сброса состояния объектов который должен реализовывать интерфейс `\Kaspi\DiContainer\Interfaces\ObjectResettersInterface`.

## Значения конфигурации для сброса состояния объекта { #resseter-types }

Конфигурация для сброса состояния объекта представляется парой ключ-значение, где ключ это идентификатор [^ContainerId] с использованием которого будет получен объект из контейнера через метод `get()`, а значение указывает контейнеру как именно будет выполнен сброс состояния объекта.

Сброс состояния объекта можно произвести через **публичный метод реализованный в этом же PHP-классе**, метод получает доступ ко всем приватным и защищённым свойствам. Для значения в конфигурации следует указать имя метода который должен выполнить сброс состояния объекта.

Например, значение конфигурации `'doReset'` для PHP-класса `\App\Services\Foo` через атрибут `\aspi\DiContainer\Attributes\Autowire` или в файле конфигураций `services.php` для PHP-класса `\App\Services\Bar`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

#[Autowire(
    resetter: 'doReset',//[!code ++]
)]
final class Foo
{
    //…
    
    public function doReset(): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}
```

```php [services.php]
<?php
declare(strict_types=1);

use App\Services\Bar;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->setResetter('doReset');//[!code ++]
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

namespace App\Services;

final class Bar
{
    //…
    
    public function doReset(): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}
```
:::

Сброс состояния объекта будет произведен если **PHP-класс реализует интерфейс `\Kaspi\DiContainer\Interfaces\ResetInterface`** и его метод `reset()`, при этом устанавливать значение для сброса состояния объекта в атрибуте или хелпер функции необязательно, значение по умолчанию `false`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

namespace App\Services;

use Kaspi\DiContainer\Interfaces\ResetInterface;//[!code ++]

final class Foo implements ResetInterface//[!code ++]
{
    //…
    
    public function reset(): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}
```
:::

Так же в качестве значения для сброса состояния объекта может быть использован любой вызываемый тип[^CallableType]. В качестве единственного аргумента вызываемый тип получает сам объект для которого выполняется сброс состояния – `callable(object $object): void`.

Вызываемый тип может быть представлен классом со статическим методом, обычной функцией или анонимной функцией:

::: code-group

```php
$resetter1 = [App\Helpers\Resettrer::class, 'doResetFoo'];

$resetter2 = 'App\resetter_bar';

$resetter3 = static function (object $object): void {
    // …
};
```

```php [Resettrer.php]
<?php
declare(strict_types=1);

namespace App\Helpers;

final class Resettrer
{
    /**
     * @param App\Services\Foo $object
     */
    public static function doResetFoo(object $object): void//[!code ++]
    {//[!code ++]
        // …//[!code ++]
    }//[!code ++]

    // …
}
```

```php [functions.php]
<?php
declare(strict_types=1);

namespace App;

if (!function_exists('App\resetter_bar')) {
    /**
     * @param App\Services\Bar $object
     */
    function resetter_bar(object $object): void {//[!code ++]
        // …//[!code ++]
    }//[!code ++]
}

```

:::


## 🚧🚧🚧
## Конфигурация контейнера и сброс состояния объектов

`$isConfigureObjectResettersFromDefinitions`

<!--@include: ../_include/term_notes.md-->