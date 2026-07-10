# ♻️ Сброс состояния объектов для долго-живущих процессов.

Некоторые сервисы которые разрешаются через метод контейнера `get(string $id)` и возвращают один и тот же объект (_php класс_),
т.е. сохраняя своё состояние в рамках всех вызовов контейнера, могут требовать сброса своего состояния в долгоживущих процессах.

Пример класса который может «накапливать данные»:
```php
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
Конфигурация для сервиса `App\Services\FooLogger` с указанием параметра `$isSingleton = true`:
```php
// /app/src/config/services_foo_logger.php
declare(strict_types=1);

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Generator;
use App\Services\FooLogger;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {
    // `$container->get(\App\Services\FooLogger::class)` всегда возвращать один и тот же объект
    yield diAutowire(FooLogger::class, isSingleton: true);
};
```
Приложение работающее по схеме «event loop»:
```php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/src/config/services_foo_logger.php')
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container;
);

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);
}
```
получая объект через `$container->get(\App\Services\FooLogger::class)` внутри приложения будет получен один и тот-же объект.
Так как разные компоненты приложения могут получать зависимость и вызывать метод `\App\Services\FooLogger::log()`,
то это приведет к «накоплению данных» в свойстве класса `\App\Services\FooLogger::$logs`.
Чтобы избежать утечек памяти, нужно использовать механизм сброса состояния объекта.

## Поддерживаемые значения конфигурации сброса состояния объекта.
Конфигурация представлена как идентификатор контейнера для сервиса получаемого через метод контейнера `get(string $id)` и значение которое будет вызвано для сброса состояния полученного объекта.

Типы значений:
- `callable(object $object): void` – вызываемое выражение с параметром типа `object` которое будет получено через метод контейнера `get(string $id)`.
- `non-empty-string` – имя публичного метода PHP класса которое будет получено через метод контейнера `get(string $id)`.


> [!TIP]
> Вызываемый тип `callable` для сброса состояния объекта так же может быть представлен классом со статическим методом или обычной функцией.
> ```php
> $resetter = [App\Qux::class, 'doReset'];
> ```
> ```php
> $resetter = 'App\functions\reset_bar_function';
> ```
>

### Автоматическое конфигурирование сервиса сброса состояния объектов на основе установленных значений в определения контейнера.
Определения контейнера поддерживающие конфигурирование сброса состояния объекта реализуют интерфейс
`\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface`.

Метод конфигурирования:
```php
setResetter(callable|string $resetter)
``` 
Параметры:
- `$resetter` – конфигурация сброса состояния объекта.

Параметр `$resetter` должен иметь сигнатуру `callable(object $object): void | non-empty-string`.

Метод `setResetter()` применим к хелпер функциям:
- [diAutowire](01-php-definition.md#diautowire).
- [diRuntime](10-runtime-definition.md#хелпер-функция-diruntime).

**Пример конфигурирования сброса состояния объекта через определение контейнера**:
```php
declare(strict_types=1);

// /app/src/Services/Foo.php
namespace App\Services;

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
    yield diAutowire(Foo::class, isSingleton: true)
        // Для сброса вызвать метод класса Foo::doReset()
        ->setResetter('doReset');
    
    yield diAutowire(Bar::class, isSingleton: true)
        // Для сброса вызвать callback функцию
        ->setResetter(static function (Bar $bar): void {
            $bar->doResetLogs();
            $bar->doResetBaz();
        });
};
```
> [!TIP]
> В составе библиотеки уже реализован PHP класс `\Kaspi\DiContainer\ObjectResetters`,
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

// получение настроенного PHP класса для сброса состояния объектов
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
> чтобы параметр `\Kaspi\DiContainer\DiContainerConfig::$isConfigureObjectResettersFromDefinitions` имел значение `true`.
>

> [!TIP]
> Если PHP класс реализует интерфейс `\Kaspi\DiContainer\Interfaces\ResetInterface`,
> то контейнер автоматически сконфигурирует определение для сброса состояния через метод класса `reset()`.
> ```php
>   final class Foo implements \Kaspi\DiContainer\Interfaces\ResetInterface
>   {
>       public function reset() : void {
>            // TODO: Implement reset() method.
>       }
>   }
> ```
> будет автоматически сконфигурирован метод `setResetter('reset')`.

### Ручная настройка сервиса сброса состояния объектов.

Для настройки следует использовать [хелпер функцию `diAutowire`](01-php-definition.md#diautowire):
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
