# TaggedAs

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\TaggedAs` внедряет коллекцию элементов из контейнера отмеченных тегом.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\TaggedAs::__construct(
    string $name,
    bool $isLazy = true,
    ?string $priorityDefaultMethod = null,
    bool $useKeys = true,
    ?string $key = null,
    ?string $keyDefaultMethod = null,
    array $containerIdExclude = [],
    bool $selfExclude = true
)
```

Параметры:
- `$name` – имя тега или FQNC [^FQCN] PHP интерфейса по которому нужно собрать коллекцию из контейнера.
- `$isLazy` – «[ленивая коллекция](../tags/index.md#collection-is-lazy)».
- `$priorityDefaultMethod` – «[метод-приоритета](../tags/collection-priority/priority-by-method-default.md)» по умолчанию.
- `$useKeys` – использовать [идентификаторы элементов коллекции](../tags/using-collecton-key/index.md).
- `$key` – [имя метаданных](../tags/tag-metadata.md) для получения идентификатора элемента коллекции.
- `$keyDefaultMethod` – «[метод идентификатора](../tags/using-collecton-key/key-from-method-default.md)» элемента коллекции по умолчанию.
- `$containerIdExclude` – [исключить из коллекции](../tags/exclide-from-collection.md#exclude-by-id) элементы по идентификатору контейнера.
- `$selfExclude` – [исключить из коллекции вызывающий PHP класс](../tags/exclide-from-collection.md#exclide-self).

<!--@include: ../_include/need_import_php_class.md-->

> [!NOTE]
> Для подробного ознакомления с механизмом формирования **приоритетов элементов коллекции, идентификаторов элементов коллекции** изучите раздел документации «[Работа с тегами в контейнере](../tags/index.md)».

## Внедрение коллекции по интерфейсу { #example-inject-collection-as-interface }

В качестве имени тега можно использовать FQCN [^FQCN] PHP интерфейса. Указав в качестве тега имя интерфейса контейнер найдет все доступные ему PHP классы реализующий указанный интерфейс и вернёт коллекцию.

Собрать коллекцию из PHP классов которые реализуют интерфейс `\App\Interfaces\QuxInterface`:

Конфигурация PHP классов:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;
use App\Interfaces\QuxInterface;

final class Foo
{
    public function __construct(
        /*
         * 🚩 Коллекция с ленивой инициализацией
         */
        #[TaggedAs(QuxInterface::class)]
        public readonly iterable $services
    ) {}
}
```

```php [QuxInterface.php]
// file: /app/src/Interfaces/QuxInterface.php
namespace App\Interfaces;

interface  QuxInterface
{    
    // …
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

```php [Bat.php]
// file: /app/src/Services/Bat.php
namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bat implements QuxInterface
{
    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespae: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}

// string(16) "App\Services\Bar"
// string(16) "App\Services\Bat"
```

PHP класс `\App\Services\Baz` не реализует интерфейс `\App\Interfaces\QuxInterface` поэтому его нет в коллекции `$foo->services`.

<!--@include: ../_include/term_notes.md-->
