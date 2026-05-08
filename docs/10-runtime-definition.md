# 🗳️ Внедрение экземпляра класса в рантайм контейнер.
В некоторых сценариях использования контейнера может возникнуть ситуация когда на момент конфигурации
и сборки контейнера экземпляр класса используемый в определении заранее неизвестен
и может быть установлен только во время выполнения контейнера,
но другие определения контейнера могут зависеть от такого сервиса (_класса_).

**Определения устанавливаемые во время выполнения контейнера, называются «Runtime definition».**

«Runtime definition» определения можно сконфигурировать через [файлы конфигураций](06-container-builder.md#загрузка-из-файлов-конфигураций)
или указать через PHP атрибут на нужном классе.

«Runtime definition» необходим чтобы контейнер знал о существовании такого определения
во время [компиляции контейнера](06-container-builder.md#компиляция-контейнера)
(_в противном случае другие определения контейнера, зависящие от «runtime definition», получат ошибку так как не найдут его в конфигурации_).

## Хелпер функция diRuntime.
Хелпер функция для конфигурационных файлов.

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use function \Kaspi\DiContainer\diRuntime;

diRuntime(string $containerIdentifier, ?string $message = null): DiDefinitionNoArgumentsInterface
```
Параметры:
- `$containerIdentifier` – идентификатора контейнера реализующий сервис, который будет добавлен позже.
- `$message` – дополнительное информационное сообщение при ошибке.

> [!WARNING]
> Хелпер функция не может быть применена к параметрам метода класса или callable выражения.
> 

> [!NOTE]
> `$containerIdentifier` может быть представлен любой не пустой строкой
> чтобы сервис можно было получить через метод контейнера `get()`.
> 
> Хелпер функция `diRuntime()` не создает конфигурацию экземпляра класса, а только предоставляет идентификатор контейнера.
>

> [!NOTE]
> [Пример конфигурирования через хелпер функцию `diRuntime`](#пример-использования-хелпер-функции-diruntime-в-конфигурационных-файлах).


## Атрибут DiRuntime.
Применятся к классу для конфигурирования «Runtime definition» в контейнере.

```php
#[DiRuntime(string $containerIdentifier = '', ?string $message = null)]
```
Параметры:
- `$containerIdentifier` – идентификатора контейнера реализующий сервис, который будет добавлен позже.
- `$message` – дополнительное информационное сообщение при ошибке.

> [!TIP]
> Атрибут может быть применен к классу несколько раз с разными значениями параметра `$containerIdentifier`.
> 

> [!TIP]
> Пустая строка в аргументе `$containerIdentifier` будет представлена как полное имя класса – **fully qualified class name** которая является идентификатором контейнера для этого php класса.

> [!NOTE]
> [Пример конфигурирования через PHP атрибут](#пример-использования-php-атрибута-diruntime-для-конфигурирования).

## Пример использования хелпер функции `diRuntime` в конфигурационных файлах.

```php
// /app/config/runtime_definitions.php
use App\Core\Kernel;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diRuntime;

return static function (DefinitionsConfiguratorInterface $configurator) {
    yield diRuntime(Kernel::class);

    yield diRuntime('secure_string');
};
```
> [!NOTE]
> Другие определения в конфигурации могут «ссылаться»
> на идентификаторы контейнера `'App\\Core\\Kernel'` и `'secure_string'`
> - через [хелпер функцию `diGet()`](01-php-definition.md#diget):
>   - `diGet(\App\Core\Kernel::class)`
>   - `diGet('secure_string')`
> - через [php атрибут `Inject`](02-attribute-definition.md#inject):
>   - `#[Inject(\App\Core\Kernel::class)]`
>   - `#[Inject('secure_string')]`
> 

Конфигурация контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src/')
    ->load('/app/config/runtime_definitions.php')
    ->build()
;
```
Установка созданных экземпляров классов в «runtime definition»:
```php
namespace Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class Kernel {
    public function __construct(private DiContainerInterface $container) {}
    // ...
    protected function initKernel(): void
    {
        // ...

        $this->container->set($this::class, $this);
        
        $secureString = \calculate_secure_string();
        $this->container->set('secure_string', $secureString);
    }
}
```
## Пример использования PHP атрибута `DiRuntime` для конфигурирования.

```php
// /app/src/RuntimeServices/Foo.php
namespace App\RuntimeServices;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime]
final class Foo {
    // ...
}
```
```php
// /app/src/RuntimeServices/Bar.php
namespace App\RuntimeServices;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime('services.bar')]
final class Bar {
    // ...
}
```
Конфигурация контейнера:
```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src/')
    ->build()
;
```
Установка созданных экземпляров классов в «runtime definition»:
```php
namespace Core;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use App\RuntimeServices\{Foo, Bar};

class Kernel {
    public function __construct(private DiContainerInterface $container) {}
    // ...
    protected function initKernel(): void
    {
        // Инициализация класс Foo
        $foo = new Foo(...\do_calcualte_foo_params());
        $this->container->set(Foo::class, $foo);
        // Инициализация класс Bar
        $bar = new Bar(...\do_calcualte_bar_params());
        $this->container->set('services.bar', $bar);
    }
}
```
