# Настройка PHP класса через сеттер методы

Внедрение зависимостей в PHP класс может происходить так же через сеттер методы. Для этого при настройке
определения контейнера используются методы хелпер функции `diAutowire::setup()` и  `diAutowire::setupImmutable()`.

## diAutowire::setup()

Использование дополнительной настройки PHP класса через [сеттер метод без учёта возвращаемого значения сеттер методом](../../documentation/php-definition/di-autowire.md#setup).

Конфигурирование:
```php
// config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function(): \Generator {

    yield 'priority_queue.get_data' => diAutowire(\SplPriorityQueue::class)
        ->setup('setExtractFlags', [\SplPriorityQueue::EXTR_DATA]);

};
```
Получение:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(...\glob(__DIR__.'/config/*.php'))
    ->build()
;

$priorityQueue = $container->get('priority_queue.get_data');
```

## diAutowire::setupImmutable()

Использование дополнительной настройки PHP класса через [сеттер метод возвращающие экземпляр PHP класса](../../documentation/php-definition/di-autowire.md#setupimmutable).

