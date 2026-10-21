# Файлы конфигураций { #title }

## Обзор { #overview }

Для объявления определений контейнера можно использовать **файлы конфигураций**. Класс `\Kaspi\DiContainer\DiContainerBuilder` предоставляет методы [`load()`](methods/load-definitions-from-file.md#load) и [`loadOverride()`](methods/load-definitions-from-file.md#loadoverride) для объявления определений контейнера и их идентификаторов[^ContainerId] из файлов конфигураций.

Файл **должен использовать ключевое слово `return`** и возвращать любой [`iterable`](https://www.php.net/manual/en/language.types.iterable.php) или `callable` тип.

В PHP `iterable` – это специальный псевдотип, который включает в себя любые массивы и объекты классов, реализующих [интерфейс `\Traversable`](https://www.php.net/manual/en/class.traversable.php).

В **файлах конфигураций** рекомендуется использование анонимных функций с [генератором](https://www.php.net/manual/en/language.generators.overview.php) (функции с ключевым словом `yield`), они позволяют отложить объявление определений до момента их фактического конфигурирования.

В конфигурации контейнера отслеживается уникальность идентификаторов контейнера[^ContainerId]. При вызове метода сборки контейнера `DiContainerBuilder::build()` будет выброшено исключение реализующее интерфейс `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface` если в файлах конфигураций будут обнаружены не уникальные идентификаторы определений контейнера.

## Конфигуратор определений в вызываемом типе { #definition-configurator-argument }

Файл конфигураций может быть представлен `callable` типом, для такого файла первым параметром контейнер передаёт аргумент [конфигуратор определений контейнера](definitions-configurator/index.md).

```php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (
    DefinitionsConfiguratorInterface $configurator//[!code warning]
) {
    // …
};
```


## Анонимная функция и генератор { #example-generator-simple }

```php
// file: /app/config/as_generator_simple.php
use Generator;
use App\Services\Foo;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments('baz val');
};
```

> [!TIP]
> Для PHP класса `App\Services\Foo` идентификатор контейнера [^ContainerId] будет сформирован автоматически через [хелпер функцию `diAutowire()`](../php-definition/di-autowire.md).

## Анонимная функция с конфигуратором и генератором { #example-generator-configurator }

```php
// file: /app/config/as_generator_with_configurator.php
use Generator;
use App\Services\Foo;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {//[!code --]
return static function (DefinitionsConfiguratorInterface $configurator): Generator {//[!code ++]
    $configurator->removeDefinition(Baz::class);// [!code highlight]
    $configurator->setParameter('params.baz', 'Lorem ipsum');// [!code highlight]

    yield diAutowire(Foo::class)
        ->bindArguments('baz val');
};
```

## Вызываемый тип с конфигуратором  { #example-callable-configurator }

```php
// file: /app/config/callable_with_configurator.php
use Generator;
use App\Services\Foo;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): Generator {//[!code --]
return static function (DefinitionsConfiguratorInterface $configurator): void {//[!code ++]
    $configurator->removeDefinition(Baz::class);
    $configurator->setParameter('params.baz', 'Lorem ipsum');
    $configurator->setDefinition(
        Foo::class,
        diAutowire(Foo::class)
            ->bindArguments('baz val')
    );
};
```

## PHP массив { #example-array }

```php
// file: /app/config/as_array.php
use App\Services\Foo;
use function Kaspi\DiContainer\diAutowire;

return [
    diAutowire(Foo::class)
        ->bindArguments('baz val')
];
```

<!--@include: ../_include/term_notes.md-->
