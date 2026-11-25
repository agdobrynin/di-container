---
outline: [2,4]
title: Руководство. Введение.
---

### Введение { #documentation }

---

### Обзор DiContainer { #overview }

Kaspi/di-container — это контейнер внедрения зависимостей для PHP >= 8.1 реализующий рекомендацию [PSR-11](https://www.php-fig.org/psr/psr-11/).

---

### Установка { #install }

Используйте менеджер зависимостей для PHP [composer](https://getcomposer.org/) чтобы установить пакет "kaspi/di-container".

```shell
composer require kaspi/di-container
```

---

### Быстрый старт { #quick-start }

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

