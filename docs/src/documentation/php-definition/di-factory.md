# diFactory

## Обзор { #overview }

Хелпер функция для конфигурирования определения контейнера как «фабрика»[^1].

> [!NOTE]
> Изучите отдельный раздел документации посвященный использованию «[фабричных методов](../07-factory.md)» для разрешения зависимостей.

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

## bindArguments()

Передача аргументов для метода фабрики.

<!--@include: ./_include/bind_arguments.md-->

## Идентификатор контейнера { #container-id }

Хелпер функция `diFactory()` не может автоматически сформировать идентификатор контейнера,
поэтому необходимо указать идентификатор контейнера для определения конфигурируемого через эту хелпер функцию.

```php
// file: /app/config/services.php
use function Kaspi\DiContainer\diFactory;
use Generator;
use Factories\FactoryMyClass;

return static function (): Generator {
    yield 'factories.my_factory' => diFactory(FactoryMyClass::class);

    // $container->get('factories.my_factory')
};
```

[^1]: В некоторых сценариях возникает необходимость для разрешения зависимостей применить шаблон проектирования «фабрика»
чтобы делегировать процесс разрешения специальному объекту – «фабрика».
