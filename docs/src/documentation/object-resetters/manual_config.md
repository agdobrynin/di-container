# Конфигурация отдельных значений сброса состояния объектов { #title }

## Обзор { #overview }

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
