# Файлы конфигураций

В `ContainerBuilder` используются загрузка из отдельных файлов для конфигурирований определений контейнера.

Конфигурационный файл может возвращать настроенные определения для контейнера либо использовать
вызов callback функции для конфигурирования через параметр [конфигуратор определений контейнера](08-definitions-configurator.md).

Файл конфигурации с возвращаемыми определениями:

```php
// /app/config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');

};
```

Комбинирование возвращаемых определений и [конфигуратора](08-definitions-configurator.md):

```php
// /app/config/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {
    $configurator->removeDefinition(Baz::class);

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');

};
```

Конфигурационный файл без возвращаемого типа с использованием только [конфигуратора определений](08-definitions-configurator.md):

```php
// /app/config/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->removeDefinition(Baz::class);
    $configurator->setParameter('adminEmail', 'admin@example.com');
    
    $configurator->setDefinition(
        Foo::class,
        diAutowire(Foo::class)
            ->bindTag('tags.foo_app');
    );
};
```

> [!IMPORTANT]
> Файл конфигурации должен использовать ключевое слово `return`.

> [!NOTE]
> Файл конфигурации может возвращать любой итерируемый тип. Например:
> - Функцию с возвращаемым типом `\Generator`.
> - простой php массив `[]`.
> - любой `callable` тип с возвращаемым типом `iterable`.

> [!TIP]
> Использование для конфигурационных файлов возвращаемого типа `\Generator` позволяет оптимизировать создание определений в контейнере
> и рекомендован для конфигурирования контейнера.

> [!TIP]
> Для некоторых определений идентификатор контейнера может быть сформирован автоматически.
> - [хелпер функция `diAutowire()`](03-php-definition.md#diautowire)
> - [хелпер функция `diRuntime()`](10-runtime-definition.md#diruntime)
> - [PHP атрибут `#[Autowire()]`](02-attribute-definition.md#autowire)
>

В рамках создаваемого контейнера отслеживается уникальность идентификаторов определений.
Если вновь загружаемый идентификатор контейнера уже присутствует, то будет выброшено исключение
`\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface` при вызове
метода сборки контейнера `DiContainerBuilder::build()`.
