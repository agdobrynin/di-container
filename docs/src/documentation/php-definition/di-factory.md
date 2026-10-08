# diFactory

## Обзор { #overview }

Хелпер функция `diFactory()` внедряет зависимость контейнера через паттерн «фабрика» [^FactoryPattern].

Сигнатура функции:

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

\Kaspi\DiContainer\diFactory(
    string|array $definition,
    ?bool $isSingleton = null
): DiDefinitionArgumentsInterface
```

Параметры:
- `$definition` – представление php класса и метода фабрики.
- `$isSingleton` – возвращать один и тот же результат (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

Функция `diFactory()` возвращает объект предоставляющий методы:
- `bindArguments()` – передать аргументы для метода фабрики.

> [!NOTE]
> Изучите отдельный раздел документации посвященный использованию «[фабричных методов](../07-factory.md)» для разрешения зависимостей.

## bindArguments()

Передача аргументов для метода фабрики.

<!--@include: ./_include/bind_arguments.md-->

## Идентификатор контейнера { #container-id }

Хелпер функция `diFactory()` не может автоматически сформировать идентификатор контейнера,
поэтому необходимо указать идентификатор контейнера для определения конфигурируемого через эту хелпер функцию.

Конфигурирование:

```php
// file: /app/config/services.php
use function Kaspi\DiContainer\diFactory;
use Generator;
use App\Services\Foo;

return static function (): Generator {
    yield 'factories.foo_factory' => diFactory([Foo::class, 'doConfigure'])
        ->bindArguments(['bar', 'baz', 'qux']);
};
```

Классы для конфигурирования:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(private Bar $bar) {}

    public function doConfigure(array $input): Bar
    {
        // Дополнительная конфигурация класса `Bar`.
        $this->bar->addTokens(...$input);
        
        return $this->bar;
    }
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …

    public function addTokens(string $token, string ...$_token): void
    {
        $this->tokens[] = $token;
        
        foreach ($_token as $_) {
            $this->tokens[] = $_;
        }
    }
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Bar;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build();

$bar = $container->get('factories.foo_factory');

var_dump($bar instanceof Bar);
// bool(true)
```
<!--@include: ../_include/term_notes.md-->
