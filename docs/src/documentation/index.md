---
outline: [2,4]
title: Руководство. Введение.
---

### Введение { #introduction }

[Внедрение зависимостей](https://ru.wikipedia.org/wiki/%D0%92%D0%BD%D0%B5%D0%B4%D1%80%D0%B5%D0%BD%D0%B8%D0%B5_%D0%B7%D0%B0%D0%B2%D0%B8%D1%81%D0%B8%D0%BC%D0%BE%D1%81%D1%82%D0%B8) важный шаблон проектирования в разработке программного обеспечения. Его идея заключается в том, что классы не создают необходимые им объекты, они получают их извне. Это обеспечивает независимость кода и делает его более пригодным для повторного использования и тестирования.

**Внедрение зависимостей** это метод написания более качественного кода.

**Контейнер внедрения зависимостей** это инструмент, помогающий автоматически собирать объекты и подставлять их зависимости по правилам конфигурации или авто-связывания (autowiring).

Рассмотрим упрощённый пример из набора PHP-классов. Эти классы представляют классический пример передачи зависимостей через конструктор:

::: code-group
```php [PostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php [!code highlight]

namespace App\Controllers;

use App\Services\Mail;
use App\Models\Post;

// Контроллер для обработки действия
final class  PostController
{
    public function __construct(private Mail $mail) {}
    // …
}
```
```php [Mail.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Mail.php [!code highlight]

namespace App\Services;

// Сервис отправки почты
final class Mail
{
    public function __construct(private Envelope $envelope) {}
    // …
}
```
```php [Envelope.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Envelope.php [!code highlight]

namespace App\Services;

// Класс для создания сообщения
final class Envelope
{
    // ...
}
```
:::

Теперь представим, чтобы получить объект класс `\App\Controllers\PostController` нам необходимо передать в конструктор объект класса `App\Services\Mail` который зависит от объекта класса `\App\Services\Envelope`. 

Получается цепочка создания объектов и передачи зависимостей через конструктор:

```php
<?php
declare(strict_types=1);

// file: /app/src/index.php [!code highlight]

require_once __DIR__ . '/../vendor/autoload.php';// [!code warning]

$envelop = new \App\Services\Envelope();
$mail = new \App\Services\Mail($envelop);
$controller = new \App\Controllers\PostController($mail);
```

Контейнер внедрения зависимостей призван сделать внедрение зависимостей более практичным и автоматизировать операции внедрения зависимостей на основании предоставленной конфигурации.

---

### Обзор DiContainer { #overview }

Kaspi/di-container — это контейнер внедрения зависимостей для PHP >= 8.1 реализующий рекомендацию [PSR-11](https://www.php-fig.org/psr/psr-11/).

#### Особенности { #features }

- **Autowiring**: автоматическое внедрение зависимостей – вы объявляете зависимости с помощью подсказок типов[^TypeHints], а контейнер делает все остальное. В большинстве случаев вам не потребуется объявлять конфигурацию объекта;
- **Lazy injection**: ленивое внедрение зависимостей позволяет отложить создание зависимостей объекта до момента их фактического использования;
- **Attribute-driven**: вы можете точечно объявлять внедрение зависимостей с помощью нативных атрибутов PHP 8+;
- **Zero configuration**: вам не потребуется объявлять конфигурацию объекта, если PHP-класс не имеет зависимостей или зависит только от других конкретных классов;
- **Definition Tags**: обеспечивает внедрение или получение коллекции элементов из контейнера отмеченные тегом с одинаковым именем;
- **Compiling the Container**: обеспечивает генерацию настроенного контейнера в высоко производительный PHP-код, оптимизированный специально для вашей конфигурации и ваших классов;
- **PSR-11**: совместим с рекомендацией PSR-11 для контейнера внедрения зависимостей;
- **Object state reset**: механизм сброса состояния объектов в долго-живущих процессах;
---

### Установка { #install }

Используйте менеджер зависимостей для PHP [composer](https://getcomposer.org/) чтобы установить пакет "kaspi/di-container".

```shell
composer require kaspi/di-container
```

---

### Быстрый старт { #quick-start }

Рассмотрим упрощённый пример из набора PHP-классов для которых необходимо обеспечить автоматическое внедрение зависимостей используя контейнер внедрения зависимостей "kaspi/di-container".

PHP-классы:

::: code-group

```php [PostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php [!code highlight]

namespace App\Controllers;

use App\Services\Mail;
use App\Models\Post;

// Контроллер для обработки действия
final class  PostController
{
    public function __construct(private Mail $mail) {}
    
    public function send(Post $post): bool
    {
        $this->mail->envelop()
            ->subject('Publication success')
            ->message('Post <'.$post->title.'> was published.');

        return $this->mail->send();
    }
}
```

```php [Mail.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Mail.php [!code highlight]

namespace App\Services;

// Сервис отправки почты
final class Mail
{
    public function __construct(private Envelope $envelope) {}
    
    public function envelop(): Envelope
    {
        return $this->envelope;
    }
    
    public function send(): bool
    {
        // отправка сообщения
        return true;
    }
}
```

```php [Envelope.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Envelope.php [!code highlight]

namespace App\Services;

// Класс для создания сообщения
final class Envelope
{
    public function subject(string $subject): void
    {
        // ...
    }

    public function message(string $message): void
    {
        // ...
    }
}
```
```php [Post.php]
<?php
declare(strict_types=1);

// file: /app/src/Models/Post.php [!code highlight]

namespace App\Models;

// Модель данных — пост в блоге
class Post
{
    public string $title;
    // ...
}
```

:::

В действии:

::: code-group
```php [в стиле PSR-11]
<?php
declare(strict_types=1);

// file: /app/src/container_psr11.php [!code highlight]

require_once __DIR__ . '/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Controllers\PostController;
use App\Models\Post;

$container = (new DiContainerBuilder())
    ->build();

if ($container->has(PostController::class)) {
    // Объект с внедренными зависимостями через конструктор[!code warning]
    $postController = $container->get(PostController::class);
    
    // заполним данные поста[!code warning]
    $post = new Post();
    $post->title = 'Lorem ipsum';

    // вызов метода полученного объекта[!code warning]
    $postController->send($post);
}
```
```php [метод контейнера call()]
<?php
declare(strict_types=1);

// file: /app/src/container_call.php [!code highlight]

require_once __DIR__ . '/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Controllers\PostController;
use App\Models\Post;

$container = (new DiContainerBuilder())
    ->build();

// заполним данные поста[!code warning]
$post = new Post();
$post->title = 'Lorem ipsum';

$container->call([PostController::class, 'send'], $post);
```
:::

---

### PSR-11

Контейнер полностью совместим с рекомендациями [PSR-11](https://www.php-fig.org/psr/psr-11/):

```php
<?php
declare(strict_types=1);

namespace Psr\Container;

interface ContainerInterface
{
    public function get($id);
 
    public function has($id);
}
```
### `call()`

Контейнер реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerCallInterface`
который предоставляет метод `call()` для вызываемых типов или значений которые могут быть преобразованы контейнером к `callable` типу.

```php
<?php
declare(strict_types=1);

use App\Services\Foo;
use Kaspi\DiContainer\DiContainerBuilder;

use function var_export;

$container = (new DiContainerBuilder())->build();

$container->call(
    static function(Foo $foo, string $name): void {
        $foo->doSomethind($name);
        sprintf('Call Foo::doSomething(%s)', var_export($name, true));
    },
    name: 'John'
);
// string(29) "Call Foo::doSomething('John')"
```

Подробнее о методе `call()` рассказано в разделе [«Метод контейнера для вызываемых типов»](call/index.md).

### set()

Контейнер реализует интерфейс `\Kaspi\DiContainer\Interfaces\DiContainerSetterInterface` который предоставляет метод `set()`
для прямой установки объекта (инстанцированный класс) в уже сформированный контейнер зависимостей.

```php
<?php
declare(strict_types=1);

use App\Services\Bar;

$bar = new Bar(
    // set some dependencies.
);

// идентификатор будет указан как 'App\Services\Bar'
$container->set($bar::class, $bar);
```

Подробнее о методе `set()` рассказано в разделе [«Динамическое добавление определений в контейнер»](container-builder/set.md).

### Особенности внедрения некоторых классов и интерфейсов { #resolve-class-interface }

При внедрении зависимостей ниже перечисленных типов всегда будет получен текущий контейнер:
- `\Psr\Container\ContainerInterface`
- `\Kaspi\DiContainer\Interfaces\DiContainerInterface`
- `\Kaspi\DiContainer\DiContainer`

Пример:
```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

class Foo
{
    public function __construct(
        public readonly ContainerInterface $container//[!code warning]
    ) {}
}

$container = (new DiContainerBuilder())->build();

var_dump($container === $container->get(Foo::class)->container);
// (bool) true
```

### Объявление внедрения зависимостей { #using }

Объявить внедрение зависимостей можно в [файлах конфигураций](php-definition/index.md) или через [атрибуты](attribute-definition/index.md), так же можно совмещать оба типа объявления.

Для сборки контейнера используйте [специальный класс `DiContainerBuilder`](container-builder/index.md).

#### Содержание документации { #toc }

* 👷‍♂️ [Сборка контейнера зависимостей](container-builder/index.md).
* ⚙️ [Конфигурация для DiContainer](container-config/index.md).
* 🐘 [Объявление в файлах конфигураций](php-definition/index.md).
* #️⃣ [Объявление через атрибуты](attribute-definition/index.md).
* 📦 [Метод контейнера `call()` для вызываемых типов](call/index.md).
* 🔖 [Теги - метки определений контейнера](tags/index.md).
* 📋 [Параметры контейнера](container-parameters/index.md).
* 🗳️ [Объявление объекта в рантайм контейнере](runtime-definition/index.md).
* 🏭 [Использование фабричных методов для внедрения зависимостей](factories/index.md).
* 🧹 [Сброс контейнера зависимостей](container-reset/index.md).
* ♻️ [Сброс состояния объектов для долго-живущих процессов](object-resetters/index.md).
* 💤 [Внедрение «ленивых» объектов контейнером (lazy injection)](lazy-injection/index.md).

<!--@include: _include/term_notes.md-->