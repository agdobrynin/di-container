---
outline: [3, 5]
title: Внедрение зависимостей PHP-классов реализующих интерфейс сторонних производителей
---
### Внедрение зависимостей PHP-классов реализующих интерфейс сторонних производителей { #overview }

Существует два способа объявить реализацию интерфейса от сторонних производителей, например из других PHP пакетов:
1. Объявить в контейнере глобально реализацию интерфейса в файле конфигураций.
2. Объявить внедрение реализации интерфейса в конкретный параметр.

Выбор способа объявления зависит от решаемой задачи.

Ниже рассмотрим конфигурацию контейнера для [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/) и PHP пакета [Monolog](https://packagist.org/packages/monolog/monolog).

#### PHP-классы { #php-classes-1 }

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/src/Services/Foo.php
<<< @/_code/cookbook/resolve-vendor-interface/src/Services/Bar.php
:::

Документация предлагает пример конфигурации логера:
```php
<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('name');
$log->pushHandler(new StreamHandler('path/to/your.log', Level::Warning));

// add records to the log
$log->warning('Foo');
$log->error('Bar');
```

Из примера видно, что для гибкой конфигурации логера мы можем выделить в [параметры контейнера](../documentation/container-parameters/index.md) несколько аргументов:
- `'name'` – имя канала логера
- `'path/to/your.log'` – файл логера
- `\Monolog\Level::Warning` – уровень логирования сообщений

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/parameters.php
:::

Для объявления реализации интерфейса можно использовать разные варианты, например, использовать анонимную функцию и [хелпер функцию `diCallable()`](../documentation/php-definition/di-callable.md), или фабричный[^FactoryPattern] метод PHP-класса и [хелпер функцию `diFactory()`](../documentation/php-definition/di-factory.md). Так же можно объявить внедрение в параметры метода реализации интерфейса через хелпер функции или атрибуты.

В зависимости от задачи выберем для объявления реализации интерфейса `\Psr\Log\LoggerInterface` [хелпер функцию `diAutowire()`](../documentation/php-definition/di-autowire.md) или [атрибут `\Kaspi\DiContainer\Attributes\Autowire`](../documentation/attribute-definition/autowire.md).

---

### Глобальное объявление реализации интерфейса { #global-config }

Глобальное объявление реализации интерфейса определённым PHP-классом означает что контейнер сможет автоматически внедрять объявленный PHP-класс как зависимость, если параметр метода или вызываемого[^CallableType] типа представлен типом `\Psr\Log\LoggerInterface`.

Важное замечание: необходимо обеспечить внедрение одного и тот же объекта реализующего интерфейс, т.е. не создавать каждый раз новый объект при внедрении зависимости. 

Интерфейс `\Psr\Log\LoggerInterface` предоставляется сторонним производителем, и прямого доступа к коду у нас нет, поэтому объявить через [атрибут `\Kaspi\DiContainer\Attributes\Service`](../documentation/attribute-definition/service.md) реализацию интерфейса у нас нет возможности.

Для решения этой задачи сделаем объявление реализации интерфейса в файле конфигураций `services.php` через хелпер функцию `diAutowire()`, где идентификатор контейнера[^ContainerId] будет представлен как FQCN[^FQCN] интерфейса для PHP-класса `\Monolog\Logger`.

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/container_build.php
<<< @/_code/cookbook/resolve-vendor-interface/services.php
<<< @/_code/cookbook/resolve-vendor-interface/parameters.php
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
object(Monolog\Logger)#12 (0) {
}
object(Monolog\Logger)#12 (0) {
}
```

> [!NOTE]
> Обратите внимание, идентификатор объекта будет одинаковым для всех внедренных объектов, в примере `#12`. Такое поведение контейнера обусловлено объявлением PHP-класса `\Monolog\Logger` значением в параметре `$isSingleton` [хелпер функции `diAutowire()`](../documentation/php-definition/di-autowire.md).

---

###  Внедрение реализации интерфейса в конкретный параметр { #param-config }

При объявлении внедрения в параметр метода или вызываемого типа[^CallableType] можно заменить [глобально объявленную реализацию интерфейса](#global-config) уточнив какую именно реализацию необходимо внедрять в конкретный параметр.

Предположим что только для PHP-класса `\App\Services\Bar` требуется внедрить логгер с другими параметрами, в частности требуется писать логи через хендлер `Monolog\Handler\SocketHandler`.

Требуемая конфигурация логера:
```php
<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\SocketHandler;

// create a log channel
$log = new Logger('app_bar_logger');
$handler = new SocketHandler('udp://remote-server:1234', Level::Debug);
$log->pushHandler($handler);

// add records to the log
$log->warning('Foo');
$log->error('Bar');
```

Выделим в отдельный файл конфигураций `services_monolog.php` объявление для PHP-класса `\Monolog\Handler\SocketHandler`.

::: code-group
<<< @/_code/cookbook/resolve-vendor-interface/services_monolog.php
:::


Воспользуемся объявлением внедрения в параметр конструктора `$logger` PHP-класса `\App\Services\Bar` через [атрибут `\Kaspi\DiContainer\Attributes\Autowire`](../documentation/attribute-definition/autowire.md), так как можем изменить код самого PHP-класса.


::: code-group
```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/Services/Bar.php [!code highlight]

namespace App\Services;

use Psr\Log\LoggerInterface;
use Kaspi\DiContainer\Attributes\Autowire;//[!code ++]
use Monolog\Logger;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as Parameter;//[!code ++]
use Kaspi\DiContainer\Attributes\Setup;//[!code ++]
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;//[!code ++]

final class Bar
{
    public function __construct(
        #[Autowire(//[!code ++]
            Logger::class,//[!code ++]
            arguments: [new Parameter('baz_logger.name')],//[!code ++]
            setups: [//[!code ++]
                'pushHandler' => new Setup(//[!code ++]
                    // получить по идентификатору контейнера объект PHP-класса[!code warning]
                    new DiGet('baz_logger.handler')//[!code ++]
                ),//[!code ++]
            ],//[!code ++]
        )]//[!code ++]
        public readonly LoggerInterface $logger
    ) {}

    // …
}

```
:::

Добавим при сборке контейнера новый файл конфигураций `services_monolog.php`:

::: code-group
```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php [!code highlight]

require_once __DIR__ . '/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->load('/app/config/services.php')//[!code highlight]
    ->loadParameters('/app/config/parameters.php')//[!code highlight]
    ->load('/app/config/services_monolog.php')//[!code ++]
    ->build();

$foo = $container->get(Foo::class);
var_dump($foo->logger->getHandlers());

$bar = $container->get(Bar::class);
var_dump($bar->logger->getHandlers());
```
<<< @/_code/cookbook/resolve-vendor-interface/services.php
<<< @/_code/cookbook/resolve-vendor-interface/parameters.php
<<< @/_code/cookbook/resolve-vendor-interface/services_monolog.php
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
array(1) {
    [0]=>
    object(Monolog\Handler\StreamHandler)#12 (0) {
    }
}
array(1) {
    [0]=>
    object(Monolog\Handler\SocketHandler)#23 (0) {
    }
}
```

<!--@include: ../documentation/_include/term_notes.md-->
