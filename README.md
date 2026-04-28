# DiContainer

Kaspi/di-container — это контейнер внедрения зависимостей для PHP >= 8.1 реализующий [рекомендацию PSR-11](https://www.php-fig.org/psr/psr-11/).

## Установка

```shell
composer require kaspi/di-container
```
### Особенности

- **Autowire** – контейнер автоматически создаёт и внедряет зависимости.
- **Zero configuration** – если класс не имеет зависимостей или зависит только от других конкретных классов, контейнеру не нужно указывать, как разрешить этот класс.
- **Php-атрибуты** для конфигурирования сервисов в контейнере.
- **Поддержка тегов** (_tags_) для определений и сервисов в контейнере.
- **Компиляция контейнера** – генерация настроенного контейнера в PHP-код оптимизированный специально для вашей конфигурации и ваших классов.
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
> Контейнер "пытается" самостоятельно определить запрашиваемую зависимость - является ли это классом или callable типом.

`DiContainer` выполнит следующие действия для `App\Controllers\PostController`:

```php
$post = new App\Controllers\PostController(
    new App\Services\Mail(
        new App\Services\Envelope()
    )
);
```
> [!TIP]
> Реализация кода в [примере](https://github.com/agdobrynin/di-container/blob/main/examples/00-start.php)

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
> Больше информации о [методе `call()`](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md)

> [!NOTE]
> Примеры использования пакета kaspi/di-container в [репозитории](https://github.com/agdobrynin/di-container-examples)

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

**Пример конфигурации:**
```php
use Kaspi\DiContainer\{DiContainerConfig, DiContainerBuilder};

$diConfig = new DiContainerConfig(
    useZeroConfigurationDefinition: false,
    useAttribute: false,
    isSingletonServiceDefault: true,
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

## Тесты
Прогнать тесты без подсчёта покрытия кода
```shell
composer test
```
Запуск тестов с проверкой покрытия кода тестами
```shell
./vendor/bin/phpunit
```

## Статический анализ кода

Для статического анализа используем пакет [PHPStan](https://github.com/phpstan/phpstan).
```shell
composer stat
```
```shell
./vendor/bin/phpstan
```

## Code style
Для приведения кода к стандартам используем php-cs-fixer который объявлен 
в dev зависимости composer-а

```shell
composer fixer
``` 

## Использование Docker образа с PHP 8.1, 8.2, 8.3, 8.4, 8.5

Указать образ с версией PHP можно в файле `.env` в ключе `PHP_IMAGE`. 
По умолчанию контейнер собирается с образом `php:8.1-cli-alpine`.

### Собрать контейнер
```shell
docker-compose build
```
### Установить зависимости php composer-а:
```shell
docker-compose run --rm php composer install
```
🔔 Если установлен `make` в системе:
```shell
make install
```
### Тесты
Запуск тестов без отчёта о покрытии кода:
```shell
docker-compose run --rm php vendor/bin/phpunit --no-coverage
```
🔔 Если установлен `make` в системе:
```shell
make test
```
Прогнать тесты с отчётом о покрытии кода:
```shell
docker-compose run --rm php vendor/bin/phpunit
```
🔔 Если установлен `make` в системе:
```shell
make test-cover
```
> ⛑ pезультаты будут в папке `.coverage-html`

### Статический анализ кода PHPStan

```shell
docker-compose run --rm php vendor/bin/phpstan
```
если установлен `make` в системе:
```shell
make stat
```
### Запуск комплексной проверки
Если установлен `make` – запуск проверки code-style, stat analyzer, tests:
```shell
make all
```
## Запуск тестов для всех поддерживаемых версий PHP через Docker образы.
Если установлен `make` – перед запуском тестов удаляется директория `vendor` и файл `composer.lock`, устанавливаются зависимости только потом выполняются тесты:
```shell
make test-supports-php
```

### Другое
Можно работать в shell оболочке в docker контейнере:
```shell
docker-compose run --rm php sh
```
