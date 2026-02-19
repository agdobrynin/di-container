# ⚙ Конфигуратор определений.
[Файлы конфигурации](06-container-builder.md#загрузка-из-файлов-конфигураций) могут использовать конфигуратор определений
передаваемый как параметр callback функции.

Конфигуратор реализует интерфейс `\Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface` и передается как первый параметр в файл конфигурации.

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/config/services/removed_services.php')
    ->build();
```
```php
// /app/config/services/removed_services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {

    $configurator->removeDefinition(\App\Kernel::class);
    $configurator->removeDefinition(\App\Core::class);

};
```

## Получить импортированное или добавленное определение.
```php
DefinitionsConfiguratorInterface::getDefinition(
    string $id
): ?\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface
```
Параметры:
- `$id` – идентификатор контейнера.

> [!NOTE]
> Важно: определение будет получено если оно было добавлено раньше вызова метода `DefinitionsConfiguratorInterface::getDefinition()`.

Пример – добавить тег к существующему определению:
```php
// /app/config/config_tags.php

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $foo = $configurator->getDefinition(Foo::class);

    if ($foo instanceof DiDefinitionTagArgumentInterface) {
        $foo->bindTag('tags.foo')
    } 
}
```

## Получить коллекцию определений по тегу.
```php
DefinitionsConfiguratorInterface::findTaggedDefinition(
    string $tag
): iterable
```
Параметры:
- `$tag` – имя тега.

> [!NOTE]
> Важно: определение будет получено если оно было добавлено раньше вызова метода `DefinitionsConfiguratorInterface::findTaggedDefinition()`.


> [!TIP]
> В качестве тега можно использовать [полное имя интерфейса](05-tags.md#interface-как-имя-тега).

Пример – добавить вызова настройки к классам реализующим интерфейс:
```php
// /app/config/api_test.php

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use App\Interfaces\FooInterface;
use App\Tests\Client;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    
    foreach ($configurator->findTaggedDefinition(FooInterface::class) as $id => $definition) {
        $definition->setup('setClient', diAutowire(Client::class));
    }

}
```

## Добавить или заменить определение.
```php
DefinitionsConfiguratorInterface::setDefinition(
    string $id,
    mixed $definition
): void
```
Параметры:
- `$id` – идентификатор контейнера.
- `$definition`  – определение контейнера.

> [!NOTE]
> Если в конфигурации уже существую определение с таким же идентификатором контейнера,
> то оно будет перезаписано.
>

Пример – заменить определение при условии:
```php
// /app/config/services_transport.php

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use App\Transport\Mail;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    
    if ('dev' === \getenv('APP_ENV')) {
        $configurator->setDefinition(
            Mail::class,
            diAutowire(Mail::class)
                ->bindArguments(
                    host: '10.10.10.10',
                    port: 20251,
                );
        );
    }
}
```
## Удаляемые определения.

Удаляет определение из контейнера, и запрещает разрешение зависимости.

```php
DefinitionsConfiguratorInterface::removeDefinition(
    string $id,
): void
```
Параметры:
- `$id` – идентификатор контейнера.

```php
// /app/config/services/removed_services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {

    $configurator->removeDefinition(\App\Kernel::class);
    $configurator->removeDefinition(\App\Core::class);

};
```

## Получение коллекции.

Получение коллекции всех ранее импортированных или добавленных определений.

```php
DefinitionsConfiguratorInterface::getDefinitions(): iterable
```

> [!NOTE]
> Важно: будут получены только те определения которые были добавлены раньше вызова метода `DefinitionsConfiguratorInterface::getDefinitions()`.
>

```php
// /app/config/services/do_something_services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {

    foreach ($configurator->getDefinitions() as $id => $definition) {
        // do something.
    }

};
```

## Загрузить определения из конфигурационных файлов.

Работает так же как методы `DiContainerBuilder::load()` и `DiContainerBuilder::loadOverride()`.
> [Подробнее о методах.](06-container-builder.md#метод-загрузки-из-файлов-конфигураций-с-отслеживанием-уникальности-идентификаторов-контейнера)

### Загрузить определения из конфигурационных файлов отслеживая уникальность идентификаторов контейнера.

```php
DefinitionsConfiguratorInterface::load(
    string $file,
    string ...$_
): void
```
Параметры:
- `$file` – полный путь к файлу конфигурации определений.
- `$_` – полный путь к файлу конфигурации определений.

```php
// /app/config/services/mix_services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->load('/app/config/config_1.php');
};
```

### Загрузить определения из конфигурационных файлов перезаписывая определения.

```php
DefinitionsConfiguratorInterface::loadOverride(
    string $file,
    string ...$_
): void
```
Параметры:
- `$file` – полный путь к файлу конфигурации определений.
- `$_` – полный путь к файлу конфигурации определений.

```php
// /app/config/services/mix_services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $configurator->loadOverride('/app/config/config_1.php');
};
```
