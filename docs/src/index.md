---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
isHome: true

hero:
    name: kaspi/di-container
    text: Контейнер внедрения зависимостей для PHP 8.1 - 8.5
    tagline: Легковесная кодовая база без внешних зависимостей и лишнего «мусора».
    image:
        src: /logo_optimize.svg
        alt: kaspi/di-container
    actions:
      - theme: brand
        text: Руководство
        link: /documentation/
      - theme: alt
        text: Рецепты
        link: /cookbook/
      - theme: alt
        text: GitHub
        link: https://github.com/agdobrynin/di-container
features:
    - title: Autowire
      details: Автоматическое разрешение зависимостей для конструктора класса или для параметров вызываемого тип.
      icon: 🛠️
    - title: Lazy injection
      details: Отложенная инициализация внедряемых объектов.
      icon: 💤
    - title: Attribute-driven
      details: Настройка без необходимости конфигурации, реализованная на базе нативных атрибутов PHP 8+.
      icon:  #️⃣ 
    - title: Zero configuration
      details: Если класс не имеет зависимостей или зависит только от других конкретных классов, контейнеру не нужно указывать, как разрешить этот класс.
      icon: 🧊
    - title: Definition Tags
      details: Получение коллекции определений и сервисов в контейнере по тегу.
      icon: 🏷️
    - title: Compiling the Container
      details: Генерация настроенного контейнера в PHP-код оптимизированный специально для вашей конфигурации и ваших классов.
      icon: 📦

---

## Обзор DiContainer { #overview }

Kaspi/di-container — это контейнер внедрения зависимостей для PHP >= 8.1 реализующий рекомендацию [PSR-11](https://www.php-fig.org/psr/psr-11/).

## Установка { #install }

Используйте менеджер зависимостей для PHP [composer](https://getcomposer.org/) чтобы установить пакет "kaspi/di-container".

```shell
composer require kaspi/di-container
```

## Быстрый старт { #quick-start }

PHP-классы:

::: code-group

```php [PostController.php]
<?php
declare(strict_types=1);

// file: /app/src/Controllers/PostController.php [!code highlight]

namespace App\Controllers;

use App\Services\Mail;
use App\Models\Post;

// Контроллер для обработки действия.
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

// Модель данных — пост в блоге.
class Post
{
    public string $title;
    // ...
}
```

:::

Собрать контейнер зависимостей:

```php
$container = (new \Kaspi\DiContainer\DiContainerBuilder())
    ->build();
```

### Получить класс с внедренными зависимостями в стиле PSR-11 { #psr-11 }
```php
use App\Controllers\PostController;
use App\Models\Post;

if ($container->has(PostController::class)) {
    // Объект с внедренными зависимостями через конструктор
    $postController = $container->get(PostController::class);
    
    // заполним данные поста
    $post = new Post();
    $post->title = 'Lorem ipsum';

    // вызов метода полученного объекта
    $postController->send($post);
}
```

### Получить результат вызова метода PHP класса через метод контейнера call() { #call }

```php
// заполним данные поста.
$post = new \App\Models\Post();
$post->title = 'Lorem ipsum';


$container->call([\App\Controllers\PostController::class, 'send'], $post);
```

> [!NOTE]
> Раздел документации о методе контейнера [call()](documentation/call/index.md).
