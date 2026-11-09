---
aside: false
---
# Конфигурация отдельных значений сброса состояния объектов { #title }

## Обзор { #overview }

Сброс состояния объектов контейнера можно ограничить, объявив в контейнере PHP-класс `\Kaspi\DiContainer\ObjectResetters` в [файле конфигураций](../container-builder/configuration_files.md) и коллекцию значений для сброса состояния указанных объектов.

Коллекция необходимая для указания сброса состояния объектов представлена парой ключ-значение, где ключ это идентификатор контейнера[^ContainerId] для получения требуемого PHP-класса через метод контейнера `get()`, а значение указывает PHP-классу `\Kaspi\DiContainer\ObjectResetters` [как необходимо выполнить сброса состояния объекта](index.md##resseter-types).

> [!IMPORTANT]
> Объявляя самостоятельно PHP-класс `\Kaspi\DiContainer\ObjectResetters` сброс состояния объектов будет выполняться только для указанных в коллекции PHP-классов. 

## Пример объявления класса ObjectResetters { #example }

PHP-классы:

::: code-group
<<< @/_code/object-resetter/src/Services/Foo.php
<<< @/_code/object-resetter/src/Services/Bar.php
<<< @/_code/object-resetter/src/Helpers/Resetters.php
<<< @/_code/object-resetter/src/Services/Baz.php
<<< @/_code/object-resetter/src/Services/Qux.php
:::

Объявить значения для сброса состояния объектов необходимо **только для PHP-классов**:
- `\App\Services\Bar` через анонимную функцию в которой вызвать методы `doResetPropertyFoo()` и `doResetPropertyBar()` сбрасывающие состояние двух свойств этого класса.
- `\App\Services\Qux` через реализуемый метод `reset()` из интерфейса `\Kaspi\DiContainer\Interfaces\ResetInterface`.

Объявить PHP-класс `\Kaspi\DiContainer\ObjectResetters` необходимо через [хелпер функцию `diAutowire()`](../php-definition/di-autowire.md), в параметре функции `$isSingleton` необходимо передать значение `true`.

::: code-group
```php
[object_resetters.php]
<?php
declare(strict_types=1);

// file: /app/config/object_resetters.php [!code highlight]

use Kaspi\DiContainer\ObjectResetters;
use App\Services\{Bar, Qux};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    $resetters = [//[!code highlight]
        Bar::class => static function (Bar $bar): void {//[!code highlight]
            $bar->doResetPropertyFoo();//[!code highlight]
            $bar->doResetPropertyBar();//[!code highlight]
        },//[!code highlight]
        Qux::class => 'reset',//[!code highlight]
    ];//[!code highlight]

    yield diAutowire(ObjectResetters::class, isSingleton: true)//[!code ++]
        ->setup('setup', [$resetters]);//[!code ++]
};
```
```php [app.php]
<?php
declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\ObjectResetters;//[!code ++]
use Framework\AppFactory;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/config/object_resetters.php')//[!code ++]
    ->build();

$app = AppFactory::create(
    // ... some dependency here
    container: $container;
);

$resetters = $container->get(ObjectResetters::class);//[!code ++]

// Event loop
while ($request = $app->getRequest()) {
    $response = $app->handle($request);
    $app->emit($response);

    $resetters->reset();//[!code ++]
}
```
:::

> [!IMPORTANT]
> Обратите внимание, что в метод `setup()` аргумент `$resetters` следует передавать как массив, введу того что параметре метода `$arguments` представлен типом `array`.

<!--@include: ../_include/term_notes.md-->