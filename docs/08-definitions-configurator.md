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
    string $id,
    ?callable $fallback = null
): mixed
```
Параметры:
- `$id` – идентификатор контейнера.
- `$fallback` – выражение для обработки когда не найдено определение.

> [!WARNING]
> В случае если определение не найдено: 
> - параметр `$fallback` установлен `null` – будет выброшено
> исключение `\Kaspi\DiContainer\Interfaces\Exceptions\NotFoundDefinitionInterface`.
> - параметр `$fallback` содержит выражение – будет выполнено выражение с возвращаемым значением.
> 
> Выражение `$fallback` принимает в качестве аргумента параметр `$id`:
> ```php
>  callable(string $id): mixed
> ```

> [!NOTE]
> Важно: определение будет получено если оно было добавлено раньше вызова метода `DefinitionsConfiguratorInterface::getDefinition()`.

Пример:

```php
// /app/config/config_tags.php

use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Exception\NotFoundDefinition;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): void {
    $foo = $configurator->getDefinition(Foo::class);

    if ($foo instanceof DiDefinitionTagArgumentInterface) {
        $foo->bindTag('tags.foo')
    }
    
    // Идентификатор `'foo_bar'` не задан, и неустановлен параметр `$fallback`.
    try {
        $configurator->getDefinition('foo_bar');
    } catch (NotFoundDefinition) {
        $configurator->setDefinition('foo_bar', 100_000);
    }

    // Идентификатор `'qux'` не задан, выражение `$fallback` вернёт `null`.
    if (null === $configurator->getDefinition('qux', static fn () => null)) {
        $configurator->setDefinition('qux', diAutowire(Foo::class));
    }
};
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

};
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
use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (DefinitionsConfiguratorInterface $configurator): void {
    
    if ('dev' === \getenv('APP_ENV')) {
        $configurator->setDefinition(
            Mail::class,
            diAutowire(Mail::class)
                ->bindArguments(
                    host: diParameter('mail.host.local'),
                    port: diParameter('mail.port.local'),
                );
        );
    }
};
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
> [!TIP]
> При использовании конфигурационных файлов с возвращаемым значением рекомендуется использовать генераторы (`\Generator`) позволяющие оптимизировать создание определений в контейнере.
> 
> ```php
> // /app/config/services/controllers_services.php
> use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
> use App\Controllers\Post;
> use App\Repositories\PostRepository;
> use function Kaspi\DiContainer\{diAutowire, diGet};
> 
> return static function (DefinitionsConfiguratorInterface $configurator): \Generator {
>       yield diAutowire(Post::class)
>           ->bindArguments(
>               postRepository: diGet(PostRepository::class),
>           )
>       ;
> };
> ```
> 

## Использование контекста для конфигурационных файлов.
При сборке и настройке контейнера можно определить дополнительный контекст для конфигурационных файлов.

Контекст определятся самим разработчиком как коллекция ключ-значение.
Передать в коллекцию по ключу можно любой тип – `mixed`, например объект, массив значений и т.д.
```php
DefinitionsConfiguratorInterface::getContext(string $name, ?callable $fallback = null): mixed
```
Параметры:
- `$name` – имя контекста.
- `$fallback`  – обработчик если контекст не найден по имени.

> [!WARNING]
> Метод `getContext()` выбросит исключение `\Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface`
> если контекст не найден по имени.
> 
> Чтобы избежать выброса исключения можно использовать `callable` выражение `$fallback` которое принимает в качестве аргумента параметр `$name`:
> ```php
>  callable(string $name): mixed
> ```


Передать значения можно через методы:
- `\Kaspi\DiContainer\Interfaces\DiContainerBuilder::addConfiguratorContexts()`
- `\Kaspi\DiContainer\Interfaces\DiContainerBuilder::setConfiguratorContext()`

Описание методов доступно в разделе «[Передача контекста для конфигурационных файлов](06-container-builder.md#передача-контекста-для-конфигурационных-файлов)»

### Пример использования контекста для конфигурационных файлов.
```php
use Kaspi\DiContainer\DiContainerBuilder;
use Dotenv\Dotenv;

$builder = (new DiContainerBuilder())
    ->import('App\\', '/app/src')
    ->load('/app/config/services/services.php');

// ...
$dotenv = Dotenv::createImmutable('/app/');
$dotenv->required('DATABASE_DSN');
$dotenv->load();

$builder->setConfiguratorContext('DATABASE_DSN', $_ENV['DATABASE_DSN']);

/*
 * объект содержащий нужные данные для конфигурирования контейнера
 * @var \App\Core $core
 */ 
$builder->setConfiguratorContext($core::class, $core);

// ...

$container = $builder->build();
```
```php
// /app/config/services/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {

    $dsn = $configurator->getContext('DATABASE_DSN');
    $core = $configurator->getContext(\App\Core::class);

    yield diAutowire(\App\Services\Foo::class)
        ->bindArguments(
            dsn: $dsn,
            foo: $core->calculateFoo(),
        );
};
```

## Конфигурирование параметров контейнера.
В конфигурационных файлах поддерживается управление «параметрами контейнера».
Подробное [описание доступных методов для параметров контейнера](09-container-parameters.md#управление-параметрами-контейнера-в-конфигураторе-определений).