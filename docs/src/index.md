---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
isHome: true

hero:
    name: kaspi/di-container
    text: Контейнер внедрения зависимостей для PHP 8.1 - 8.5
    tagline: Легковесная кодовая база без внешних зависимостей и лишнего «мусора».
    image:
        src: /logo2.svg
        alt: kaspi/di-container
    actions:
      - theme: brand
        text: Руководство
        link: /documentation
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

## Установка

```shell
composer require kaspi/di-container
```

## Быстрый старт

```php
// Создать контейнер
$container = (new \Kaspi\DiContainer\DiContainerBuilder())
    ->import('App\\', src: '/var/www/app/src')
    ->build();
```
> [!NOTE]
> Раздел документации о [DiContainerBuilder](00-container-builder.md).

#### Получить класс с внедренными зависимостями в стиле PSR-11:
```php
if ($container->has(\App\Controllers\PostController::class)) {
    // Инстанцированный класс с внедренными зависимостями через конструктор
    $postController = $container->get(\App\Controllers\PostController::class);
    
    // заполним данные поста.
    $post = new \App\Models\Post();
    $post->title = 'Lorem ipsum';
    
    $postController->send($post);
}
```
#### Получить результат вызова метода PHP класса через метод контейнера `call()`:
```php
// заполним данные поста.
$post = new \App\Models\Post();
$post->title = 'Lorem ipsum';

$container->call([\App\Controllers\PostController::class, 'send'], [$post]);
```
> [!NOTE]
> Раздел документации о методе контейнера [call()](04-call-method.md).

#### Классы в проекте:

::: code-group

```php [PostController.php]
// /var/www/app/src/Controllers/PostController.php
namespace App\Controllers;

use App\Services\Mail;
use App\Models\Post;

// Контроллер для обработки действия.
class  PostController {
    public function __construct(private Mail $mail) {}
    
    public function send(Post $post): bool {
        $this->mail->envelop()
            ->subject('Publication success')
            ->message('Post <'.$post->title.'> was published.');

        return $this->mail->send();
    }
}
```

```php [Mail.php]
// /var/www/app/src/Services/Mail.php
namespace App\Services;

// Сервис отправки почты
class Mail {
    public function __construct(private Envelope $envelope) {}
    
    public function envelop(): Envelope {
        return $this->envelope;
    }
    
    public function send(): bool {
        // отправка сообщения
    }
}
```

```php [Envelope.php]
// /var/www/app/src/Services/Envelope.php
namespace App\Services;

// Класс для создания сообщения
class Envelope {
    public function subject(string $subject): void {
        // ...
    }

    public function message(string $message): void {
        // ...
    }
}
```
```php [Post.php]
// /var/www/app/src/Models/Post.php
namespace App\Models;

// Модель данных — пост в блоге.
class Post {
    public string $title;
    // ...
}
```

:::

<VPButton text="Изучите руководство" href="/documentation" theme="brand" />
