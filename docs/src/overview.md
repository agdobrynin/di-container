# Быстрый старт

## Установка

```shell
composer require kaspi/di-container
```
## Быстрый старт

📂 Определения классов:
```php
// src/Services/Envelope.php
namespace App\Services;

// Класс для создания сообщения
class Envelope {
    public function subject(string $subject): static {
        // ...
        return $this;
    }
    
    public function message(string $message): static {
        // ...
        return $this;
    }
}
```
```php
// src/Services/Mail.php
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
```php
// src/Models/Post.php
namespace App\Models;

// Модель данных — пост в блоге.
class Post {
    public string $title;
    // ...
}
```

```php
// src/Controllers/PostController.php
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
👷‍♂️ Создание контейнера и разрешение зависимостей:
```php
use App\Controllers\PostController;
use App\Models\Post;
use Kaspi\DiContainer\DiContainerBuilder;

// Создать контейнер.
$container = (new DiContainerBuilder())
    ->build();

// more code...

// получить класс PostController с внедренным сервисом Mail.
$postController = $container->get(PostController::class);
//Заполняем модель данными.
$post = new Post();
$post->title = 'Publication about DiContainer';
// Выполняем метод `PostController::post()`.
$postController->send($post);
```
> [!NOTE]
> Контейнер "пытается" самостоятельно определить запрашиваемую зависимость - является ли это классом или `callable` типом.

`DiContainer` выполнит следующие действия для `App\Controllers\PostController`:

```php
$post = new App\Controllers\PostController(
    new App\Services\Mail(
        new App\Services\Envelope()
    )
);
```

Другой вариант для примера выше можно использовать для получения результата метод контейнера `call()`:
```php
use App\Controllers\PostController;
use App\Models\Post;

$post = new Post();
$post->title = 'Publication about DiContainer';

// ...

// получить класс PostController с внедренным сервисом Mail и выполнить метод "send"
// с передачей именованного аргумента
$container->call(
    definition: [PostController::class, 'send'],
    post: $post
);

```
> [!TIP]
> Больше информации о [методе `call()`](03-call-method.md)

### Конфигурирование DiContainer

Для конфигурирования контейнера используется класс
`\Kaspi\DiContainer\DiContainerConfig`
который реализует интерфейс
`\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface`.

#### Нулевая конфигурация для внедрения зависимостей:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isUseZeroConfigurationDefinition(): bool;
```
**Не нужно указывать контейнеру, как разрешить конкретный PHP-класс**
если класс не имеет зависимостей, или зависит только от других конкретных классов,
или зависит от ранее сконфигурированных классов (интерфейсов).

#### Использовать Php-атрибуты для конфигурирования:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isUseAttribute(): bool;
```
Предоставляет возможность [конфигурирования определений на базе PHP атрибутов](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).

#### Разрешать зависимость как синглтон:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isSingletonServiceDefault(): bool;
```
Для определений в контейнере можно указать как разрешать сервис – возвращать всегда одни и тот же объект
или создавать объект сервиса каждый раз при получении через метод контейнера `get()`.
Для определений контейнера у которых неуказан способ получения через метод контейнера `get()`
применяется значение по умолчанию из конфигурации.

#### Конфигурировать сервис сброса состояния объектов из определений контейнера:
```php
\Kaspi\DiContainer\Interfaces\DiContainerConfigInterface::isConfigureObjectResettersFromDefinitions(): bool;
```
В долгоживущих процессах некоторые сервисы контейнера могут требовать сброса своего состояния.
Для сброса состояния таких сервисов может быть использован механизм автоматического конфигурирования
сервиса на основе конфигурации определений контейнера. Подробности в разделе [«Сброс состояния объектов для долго-живущих процессов».](https://github.com/agdobrynin/di-container/blob/main/docs/12-object-resetters.md)

**Пример конфигурации:**
```php
use Kaspi\DiContainer\{DiContainerConfig, DiContainerBuilder};

$diConfig = new DiContainerConfig(
    useZeroConfigurationDefinition: false,
    useAttribute: false,
    isSingletonServiceDefault: true,
    isConfigureObjectResettersFromDefinitions: false,
);

// передать настройки в построитель контейнера
$container = (new DiContainerBuilder(containerConfig: $diConfig))
    ->build();
```

### Особенности получения некоторых классов и интерфейсов.

Некоторые интерфейсы или классы всегда возвращают текущий контейнер зависимостей.
При разрешении зависимости для интерфейсов и классов:
- `Psr\Container\ContainerInterface::class`
- `Kaspi\DiContainer\Interfaces\DiContainerInterface::class`
- `Kaspi\DiContainer\DiContainer::class`

будет получен текущий контейнер зависимостей.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

function testFunc(ContainerInterface $c) {
    return $c;
}

$container = (new DiContainerBuilder())->build();

var_dump($container->call('testFunc') instanceof DiContainer); // true
var_dump($container->call('testFunc') instanceof ContainerInterface); // true
```

```php
use Kaspi\DiContainer\DiContainerBuilder;
use Psr\Container\ContainerInterface;

class TestClass {
    public function __construct(
        public ContainerInterface $container
    ) {}
}

$container = (new DiContainerBuilder())->build();

var_dump($container->get(TestClass::class)->container instanceof ContainerInterface); // true
```

### 🧰 Подробное описание конфигурирования и использования
* 👷‍♂️ [Инструмент для сборки контейнера зависимостей **DiContainerBuilder**](https://github.com/agdobrynin/di-container/blob/main/docs/06-container-builder.md).
* 🐘 [DiContainer с конфигурированием **в стиле php определений**](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md).
* #️⃣ [DiContainer c конфигурированием **через PHP атрибуты**](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).
* 📦 [Метод контейнера `call()`](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md) для вызова чистых `callable` типов и дополнительных определений.
* 🔖 [Тэгирование определений и сервисов](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).
* 📋 [Параметры контейнера](https://github.com/agdobrynin/di-container/blob/main/docs/09-container-parameters.md).
* 🗳️ [Внедрение экземпляра класса в рантайм контейнер](https://github.com/agdobrynin/di-container/blob/main/docs/10-runtime-definition.md).
* 🧹 [Сброс контейнера зависимостей](https://github.com/agdobrynin/di-container/blob/main/docs/11-container-reset.md).
* ♻️ [Сброс состояния объектов для долго-живущих процессов](https://github.com/agdobrynin/di-container/blob/main/docs/12-object-resetters.md).
* 💤 [Внедрение «ленивых» объектов контейнером (lazy injection)](https://github.com/agdobrynin/di-container/blob/main/docs/14-lazy-injection.md)
