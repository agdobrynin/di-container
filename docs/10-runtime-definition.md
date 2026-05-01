# 🗳️ Внедрение экземпляра класса в рантайм контейнер.
В некоторых сценариях использования контейнера может возникнуть ситуация когда на момент конфигурации
и сборки контейнера экземпляр класса используемый в определении заранее неизвестен
и может быть установлен только во время выполнения контейнера,
но другие определения контейнера могут зависеть от такого сервиса (_класса_).

Определения устанавливаемые во время выполнения контейнера, называются «Runtime definition».
В файлах конфигураций используется «Runtime definition» чтобы контейнер знал о существовании такого определения
во время [компиляции контейнера](06-container-builder.md#компиляция-контейнера)
(_в противном случае другие определения контейнера, зависящие от «runtime definition», получат ошибку так как не найдут его в конфигурации_).

## diRuntime
Хелпер функция для конфигурации «Runtime definition» в списке зарегистрированных определений.

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use function \Kaspi\DiContainer\diRuntime;

diRuntime(string $containerIdentifier, ?string $message = null): DiDefinitionNoArgumentsInterface
```
Параметры:
- `$containerIdentifier` – идентификатора контейнера (php класс, интерфейс или непустая строка) реализующий сервис, который будет добавлен позже.
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

## Пример использования.
Конфигурация специализированных определений:
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
