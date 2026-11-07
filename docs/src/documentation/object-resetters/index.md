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

Конфигурация для сброса состояния объектов представлена парой ключ-значение, где ключ это идентификатор контейнера[^ContainerId], а значение представленное типами `string`, `false` или `callable` указывает как необходимо выполнять сброса состояния объекта.

**Типы значений для сброса состояния объекта:**
- `non-empty-string` – имя публичного метода PHP-класса.
- `false` – если PHP-классом реализован интерфейс `\Kaspi\DiContainer\Interfaces\ResetInterface`, то выполнять метод `reset()`.
- `callable(object $object): void` – вызываемый тип[^CallableType] с параметром типа `object`.

Выберем в качестве сброса состояния объекта реализацию публичного метода в этом же классе `\App\Services\FooLogger::doReset()` и объявления в параметре `$resetter` атрибута `\Kaspi\DiContainer\Attributes\Autowire`:

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

Объявить конфигурацию для сброса состояния объектов можно следующими способами:

- [Автоматическая конфигурация](auto_config.md) PHP-класса `\Kaspi\DiContainer\ObjectResetters` контейнером.
- [Объявление в контейнере](manual_config.md) PHP-класса `\Kaspi\DiContainer\ObjectResetters` с указанием необходимых значений для сброса состояния объектов.

> [!TIP]
> Для случаев когда невозможно изменить код PHP-класса, чтобы реализовать метод сброса состояния объекта, например PHP-класс из пакета стороннего производителя, можно [воспользоваться механизмом рефлексии объектов](reset_vendor_class.md).

> [!NOTE]
> Разработчик может реализовать собственный PHP-класс сброса состояния объектов который должен реализовывать интерфейс `\Kaspi\DiContainer\Interfaces\ObjectResettersInterface`.

## Типы значений для сброса состояния объекта { #resseter-types }

🚧🚧🚧🚧
- `non-empty-string` – имя публичного метода PHP-класса которое будет получено через метод контейнера `get(string $id)`.
- `false` – если PHP-классом реализован интерфейс `\Kaspi\DiContainer\Interfaces\ResetInterface`, то выполнять метод `reset()`.
- `callable(object $object): void` – вызываемый тип[^CallableType] с параметром типа `object`.

🚧🚧🚧🚧

## 🚧🚧🚧🚧

## Поддерживаемые значения конфигурации сброса состояния объекта.
Конфигурация представлена как идентификатор контейнера для объекта (_PHP-класса_) получаемого через метод контейнера `get(string $id)` и значение которое будет вызвано для сброса состояния полученного объекта.



> [!TIP]
> Вызываемый тип `callable` для сброса состояния объекта так же может быть представлен классом со статическим методом или обычной функцией.
> ```php
> $resetter = [App\Qux::class, 'doReset'];
> ```
> ```php
> $resetter = 'App\functions\reset_bar_function';
> ```

> [!TIP]
> При установленном значении `false` для сброса состояния объекта, будет проверено реализуется ли PHP-классом интерфейс `\Kaspi\DiContainer\Interfaces\ResetInterface`,
> если реализует, то контейнер автоматически сконфигурирует определение для сброса состояния через метод класса `reset()`.
> ```php
>   final class Foo implements \Kaspi\DiContainer\Interfaces\ResetInterface
>   {
>       public function reset() : void {
>            // TODO: Implement reset() method.
>       }
>   }
> ```
> будет автоматически сконфигурирован метод `setResetter('reset')`.

### Автоматическое конфигурирование сервиса сброса состояния объектов на основе установленных значений в определения контейнера.
При [настойке определений контейнера](03-php-definition.md) через конфигурационные файлы, некоторые хелпер функции реализуют интерфейс
`\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface` который предоставляет настройку сброса состояния объекта.

Метод конфигурирования:
```php
setResetter(callable|false|string $resetter)
``` 
Параметры:
- `$resetter` – конфигурация сброса состояния объекта.

Метод `setResetter()` применим к хелпер функциям:
- [diAutowire](03-php-definition.md#diautowire).
- [diRuntime](../runtime-definition/index.md#хелпер-функция-diruntime).

Установить конфигурацию сброса для нужного объекта так же можно через значение в PHP атрибутах:
- [Autowire](02-attribute-definition.md#autowire).
- [DiRuntime](../runtime-definition/index.md#атрибут-diruntime).

> [!TIP]
> Для приложений использующих **PHP 8.5 и выше** параметр `$resetter`
> у атрибутов [Autowire](02-attribute-definition.md#autowire) и [DiRuntime](../runtime-definition/index.md#атрибут-diruntime)
> может быть представлен как [статическая анонимная функция](https://www.php.net/manual/ru/functions.anonymous.php#functions.anonymous):
> ```php
>  use Kaspi\DiContainer\Attributes\Autowire;
>  
>  #[Autowire(
>       isSingleton: true,
>       resetter: static function (Foo $foo): void {
>           // реализация механизма сброса объекта.
>       }
>  )]
>  final class Foo {
>    //
>  }
> ```

**Пример конфигурирования сброса состояния объекта через определение контейнера**.

Конфигурирование сервиса через [PHP атрибут Autowire](02-attribute-definition.md#autowire):
```php
declare(strict_types=1);

// /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Autowire;

// Для сброса вызвать метод класса Foo::doReset()
#[Autowire(isSingleton: true, resetter: 'doReset')]
final class Foo {
    // other method here

    public function doReset(): void {
        // reset state here
    }
}
```
```php
declare(strict_types=1);

// /app/src/Services/Bar.php
namespace App\Services;

// конфигурировать класс в файле 
// `'/app/src/config/services_with_resetters.php'`
final class Bar {
    // other method here
    
    public function doResetLogs(): void {
        // reset state here
    }
    
    public function doResetBaz(): void {
        // reset state here
    }
}
```
```php
// /app/src/config/services_with_resetters.php
declare(strict_types=1);

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Generator;
use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    yield diAutowire(Bar::class, isSingleton: true)
        // Для сброса вызвать callback функцию
        ->setResetter(static function (Bar $bar): void {
            $bar->doResetLogs();
            $bar->doResetBaz();
        });
};
```
> [!TIP]
> В составе библиотеки уже реализован PHP-класс `\Kaspi\DiContainer\ObjectResetters`,
> который будет настраивать сброс состояния объектов из определений контейнера и выполнять их:
> 
```php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\ObjectResetters;
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/src/config/services.php')
    ->load('/app/src/config/services_with_resetters.php')
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container;
);

// получение настроенного PHP-класса для сброса состояния объектов
$resetters = $container->get(ObjectResetters::class);

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);
    // сбросить состояние объектов.
    $resetters->reset();
}
```
> [!IMPORTANT]
> Для автоматической конфигурации сброса состояния объектов необходимо
> чтобы параметр `\Kaspi\DiContainer\DiContainerConfig::$isConfigureObjectResettersFromDefinitions` имел значение `true`,
> по умолчанию параметр имеет значение `true`.  
>

### Ручная настройка сервиса сброса состояния объектов.

Для настройки следует использовать [хелпер функцию `diAutowire`](03-php-definition.md#diautowire):
```php
// /app/src/config/object_resetters.php
declare(strict_types=1);

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\ObjectResetters;
use Generator;
use App\Services\{Foo, Bar};

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    // Ключ массива это идентификатор контейнера для получения объекта
    // которому необходимо сбросить состояние, а значение это конфигурация для сброса объекта.
    $resettersConfiguration = [
        // Для сброса вызвать метод класса Foo::doReset()
        Foo::class => 'doReset',
        // Для сброса вызвать callback функцию
        Bar::class => static function (Bar $bar): void {
            $bar->doResetLogs();
            $bar->doResetBaz();
        },
    ];

    // `$container->get(\Kaspi\DiContainer\ObjectResetters::class)` всегда возвращать один и тот же объект
    yield diAutowire(ObjectResetters::class, isSingleton: true)
        // передать настройку через сеттер-метод `ObjectResetters::setup()`
        ->setup('setup', arguments: [$resettersConfiguration]);
};
```
```php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\ObjectResetters;
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/src/config/object_resetters.php')
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container;
);

// получение настроенного сервиса для сброса состояния объектов
$resetters = $container->get(ObjectResetters::class);

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);
    // сбросить состояние объектов.
    $resetters->reset();
}
```
> [!IMPORTANT]
> Ручная настройка сброса состояния объектов **отключает**
> [автоматическое конфигурирование из определений контейнера](#автоматическое-конфигурирование-сервиса-сброса-состояния-объектов-на-основе-установленных-значений-в-определения-контейнера).
>

<!--@include: ../_include/term_notes.md-->