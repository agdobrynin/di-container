# Объявление «параметров контейнера» { #title }

## Обзор { #overview }

Объявление «параметров контейнера» производится через класс `\Kaspi\DiContainer\DiContainerBuilder`, или в файлах конфигурации через [конфигуратор определений](../08-definitions-configurator.md).

> [!IMPORTANT]
> Порядок загрузки конфигураций параметров важен – при совпадении имен параметров контейнера значение будет перезаписано более поздним вызовом в конфигурации.

## Объявление «параметров контейнера» из файлов { #load-parameters }

```php
Kaspi\DiContainer\DiContainerBuilder::loadParameters(
    string $file,
    string ...$_
): static
```

Параметры:
- `$file` – полный путь к файлу описывающий конфигурацию параметров.
- `$_` – дополнительные файлы конфигураций параметров контейнера.

> [!IMPORTANT]
> Файл конфигурации параметров контейнера должен начинаться с ключевого слова `return` и возвращать любой итерируемый тип или `callable` тип который также должен возвращать любой итерируемый тип.

## Объявление «параметров контейнера» из коллекции { #add-parameters }

```php
Kaspi\DiContainer\DiContainerBuilder::addParameters(
    iterable $params
): static
```

Параметры:
- `$params` – коллекция ключ-значение конфигурации параметров.

## Объявление одного «параметра контейнера» { #set-parameter }

```php
Kaspi\DiContainer\DiContainerBuilder::setParameter(
    string $name,
    array|int|float|string|bool|null|\UnitEnum $value
): static
```

Параметры:
- `$name` – имя параметра.
- `$value` – значение параметра.

## Объявление «параметров контейнера» в конфигураторе определений { #cofigurator }

Объявлять и управлять «параметрами контейнера» так же можно в файлах конфигурации через [конфигуратор определений](../08-definitions-configurator.md).

> [!IMPORTANT]
> Все добавленные ранее параметры в конфигурацию могут быть замены новыми значениями если имя параметра совпадает.

Интерфейс `\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface` предоставляем методы для объявления параметров контейнера:

- `DefinitionsConfiguratorInterface::loadParameters()` – объявление «параметров контейнера» из файлов.
- `DefinitionsConfiguratorInterface::addParameters()` – объявление «параметров контейнера» из коллекции.
- `DefinitionsConfiguratorInterface::setParameter()` – объявление параметра и его значения.
- `DefinitionsConfiguratorInterface::removeParameter()` – удаление параметра по имени из конфигурации контейнера.
- `DefinitionsConfiguratorInterface::hasParameter()` – проверка на доступность параметра по имени.

```php
use Generator;
use App\Services\Foo;
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): Generator {

    $configurator->loadParameters('/app/config/more_app_params.php');

    if (!$configurator->hasParameter('debug')) {
        $configurator->setParameter('baz', null);
    } else {
        $configurator->removeParameter('foo');
    }

    yield diAutowire(Foo::class)
        ->bindArguments(
            diParameter('baz')
        )
    ;

};
```
