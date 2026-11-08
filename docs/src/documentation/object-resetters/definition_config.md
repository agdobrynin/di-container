# Конфигурация из определений контейнера { #title }

## Обзор { #overview }

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