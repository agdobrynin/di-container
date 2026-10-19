# Файлы конфигураций определений контейнера

В `\Kaspi\DiContainer\DiContainerBuilder` используются загрузка из отдельных файлов для объявления определений контейнера и их идентификаторов[^ContainerId].

Файл конфигурации с возвращаемыми определениями:

::: code-group

```php [/app/config/services.php]
use App\Services\Foo;
use function Kaspi\DiContainer\diAutowire;

return static function (): \Generator {

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');

};
```

:::

> [!TIP]
> Для определения `App\Services\Foo` идентификатор контейнера [^ContainerId] будет сформирован автоматически через [хелпер функцию `diAutowire()`](../php-definition/di-autowire.md).

Комбинирование возвращаемых определений и [конфигуратора](../08-definitions-configurator.md):

::: code-group

```php [/app/config/services.php]
use App\Services\{Foo, Baz};
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {
    $configurator->removeDefinition(Baz::class);

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');

};
```

:::

> [!TIP]
> Для определения `App\Services\Foo` идентификатор контейнера будет сформирован автоматически через [хелпер функцию `diAutowire()`](../php-definition/di-autowire.md).

Конфигурационный файл без возвращаемого типа с использованием только [конфигуратора определений](../08-definitions-configurator.md):

::: code-group

```php[/app/config/services.php]
use App\Services\{Foo, Baz};
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
> Для некоторых определений контейнера идентификатор [^ContainerId] может быть сформирован автоматически.
> - [хелпер функция `diAutowire()`](../php-definition/di-autowire.md)
> - [хелпер функция `diRuntime()`](../php-definition/di-runtime.md)
> - [PHP атрибут `#[Autowire()]`](../attribute-definition/autowire.md)
>

В рамках контейнера отслеживается уникальность идентификаторов контейнера. Если вновь загружаемый идентификатор контейнера уже присутствует, то будет выброшено исключение `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface` при вызове метода сборки контейнера `DiContainerBuilder::build()`.

<!--@include: ../_include/term_notes.md-->
