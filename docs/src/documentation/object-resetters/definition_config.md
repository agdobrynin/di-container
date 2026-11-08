# Конфигурация из определений контейнера { #title }

## Обзор { #overview }

Объявляя PHP-класс в контейнере через хелпер функцию или атрибут можно установить значение указывающее контейнеру как именно будет выполнен сброс состояния объекта.

Объявлять PHP-класс `\Kaspi\DiContainer\ObjectResetters` и его конфигурацию не требуется, контейнер самостоятельно создаст конфигурацию для сброса состояния объектов и объявит PHP-класс `\Kaspi\DiContainer\ObjectResetters`.

При объявлении PHP-класса в [файлах конфигураций](../container-builder/configuration_files.md) для указания значения для сброса состояния объекта необходимо использовать метод `setResetter()` предоставляемый хелпер функциями:
- [`diAutowire()`](../php-definition/di-autowire.md#setresetter).
- [`diRuntime()`](../php-definition/di-runtime.md#setresetter).

При объявлении PHP-класса через атрибуты необходимо передавать значение сброса состояния объекта в параметре конструктора `$resetter`:
- [атрибута `\Kaspi\DiContainer\Attributes\Autowire`](../attribute-definition/autowire.md).
- [атрибута `\Kaspi\DiContainer\Attributes\DiRuntime`](../attribute-definition/di-runtime.md).

> [!TIP]
> Для приложений использующих **PHP 8.5 и выше** параметр конструктора `$resetter` у атрибута может быть представлен как [статическая анонимная функция](https://www.php.net/manual/ru/functions.anonymous.php#functions.anonymous):
> ```php
>  use Kaspi\DiContainer\Attributes\Autowire;
>  
>  #[Autowire(
>       isSingleton: true,
>       resetter: static function (Foo $foo): void {//[!code ++]
>           // реализация механизма сброса объекта.
>       }//[!code ++]
>  )]
>  final class Foo
>  {
>    //
>  }
> ```


## Пример конфигурирования сброса состояния объекта { #example }

PHP-классы:

::: code-group
<<< @/_code/object-resetter/src/Foo.php
<<< @/_code/object-resetter/src/Bar.php
<<< @/_code/object-resetter/src/Baz.php
<<< @/_code/object-resetter/src/Qux.php
::: 

Необходима конфигурация сброса состояния объектов PHP-классов `\App\Services\Foo`, `\App\Services\Bar` и `\App\Services\Baz`. 

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