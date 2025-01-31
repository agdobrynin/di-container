# DiContainer

Kaspi/di-container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0.

## Установка

```shell
composer require kaspi/di-container
```
### Особенности

- **Autowire** - контейнер автоматически создаёт и внедряет зависимости.
- Поддержка "**zero configuration for dependency injection**" - когда ненужно объявлять зависимость если класс существуют и может быть запрошен по "PSR-4 auto loading" с автоматическим внедрением зависимостей.
- Поддержка **Php-атрибутов** для конфигурирования сервисов в контейнере.
- Поддержка тэгов (_tags_) для определений и сервисов в контейнере.
## Быстрый старт
Определения классов:
```php
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
namespace App\Models;
// Модель данных — пост в блоге.
class Post {
    public string $title;
    // ...
}
```

```php
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

```php
// Создать контейнер.
$container = (new Kaspi\DiContainer\DiContainerFactory())->make();

// more code...

//Заполняем модель данными.
$post = new App\Models\Post();
$post->title = 'Publication about DiContainer';

// получить класс PostController с внедренным сервисом Mail и выполнить метод "send"
$postController = $container->get(App\Controllers\PostController::class);
$postController->send($post);
```
> Контейнер "пытается" самостоятельно определить запрашиваемую зависимость - является ли это классом или callable типом.

🛠 Фактически `DiContainer` выполнит следующие действия для `App\Controllers\PostController`:

```php
$post = new App\Controllers\PostController(
    new App\Services\Mail(
        new App\Services\Envelope()
    )
);
```
🚩 Реализация кода в [примере](https://github.com/agdobrynin/di-container/blob/main/examples/00-start.php)

Другой вариант для примера выше можно использовать для получения результата метод `call`:
```php
$post = new App\Models\Post();
$post->title = 'Publication about DiContainer';

// ...

// получить класс PostController с внедренным сервисом Mail и выполнить метод "send"
$container->call(
    definition: [App\Controllers\PostController::class,'send'],
    arguments: ['post' => $post]
);

```
> 📝 [DiContainer::call](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md)

🦄 Примеры использования пакета kaspi/di-container в [репозитории](https://github.com/agdobrynin/di-container-examples)

### Конфигурирование DiContainer

Для конфигурирования параметров используется класс:
`Kaspi\DiContainer\DiContainerConfig::class` который имплементируют интерфейс `Kaspi\DiContainer\Interfaces\DiContainerConfigInterface`

```php
use Kaspi\DiContainer\{DiContainerConfig, DiContainer};

$diConfig = new DiContainerConfig(
    // Ненужно объявлять каждую зависимость.
    // Если класс, функция или интерфейс существуют
    // и может быть запрошен через автозагрузку (например через composer),
    // то объявлять каждое определение необязательно.
    useZeroConfigurationDefinition: true,
    // Использовать Php-атрибуты для объявления определений контейнера.
    useAttribute: true,
    // Сервис (объект) создавать как одиночку (singleton pattern).
    isSingletonServiceDefault: false,
);
// передать настройки в контейнер
$container = new DiContainer(config: $diConfig);
```
Или использовать фабрику с настроенными по умолчанию параметрами:
```php
use Kaspi\DiContainer\DiContainerFactory;

$container = (new DiContainerFactory())->make(definitions: []);
```

⚙ При попытке разрешить зависимость через метод `get` или аргумент конструктора, или метода:

- `$container->get(Psr\Container\ContainerInterface::class);`
- `$container->get(Kaspi\DiContainer\DiContainer::class);`
- `$container->get(Kaspi\DiContainer\Interfaces\DiContainerInterface::class);`

| будет получен текущий class `Kaspi\DiContainer\DiContainer::class`

```php
use Kaspi\DiContainer\DiContainerFactory;

function testFunc(\Psr\Container\ContainerInterface $c) {
    return $c;
}

$container = (new DiContainerFactory())->make();
$container->call('testFunc') instanceof DiContainer; // true
```
```php
use Kaspi\DiContainer\DiContainerFactory;
use Psr\Container\ContainerInterface;

class TestClass {
    public function __construct(
        public ContainerInterface $container
    ) {}
}

$container = (new DiContainerFactory())->make();
$container->get(TestClass::class)->container instanceof DiContainer; // true
```

### 📁 DefinitionsLoader
🛠 Загрузка конфигурации для контейнера зависимостей из нескольких файлов.
Подробное описание использования [DefinitionsLoader](https://github.com/agdobrynin/di-container/blob/main/docs/04-definitions-loader.md).

### 🧰 Подробное описание конфигурирования и использования

* [DiContainer с конфигурированием на основе php-определений](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md).
* [DiContainer c конфигурированием через PHP атрибуты](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).
* [DiContainer::call](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md) для вызова чистых `callable` типов и дополнительных определений.
* [Тэгирование определений и сервисов](https://github.com/agdobrynin/di-container/blob/main/docs/05-tags.md).

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

Для статического анализа используем пакет [Phan](https://github.com/phan/phan).

Запуск без PHP расширения [PHP AST](https://github.com/nikic/php-ast)

```shell
./vendor/bin/phan --allow-polyfill-parser
```

## Code style
Для приведения кода к стандартам используем php-cs-fixer который объявлен 
в dev зависимости composer-а

```shell
composer fixer
``` 

## Использование Docker образа с PHP 8.0, 8.1, 8.2, 8.3

Указать образ с версией PHP можно в файле `.env` в ключе `PHP_IMAGE`. 
По умолчанию контейнер собирается с образом `php:8.0-cli-alpine`.

Собрать контейнер
```shell
docker-compose build
```
Установить зависимости php composer-а:
```shell
docker-compose run --rm php composer install
```
Прогнать тесты с отчетом о покрытии кода
```shell
docker-compose run --rm php vendor/bin/phpunit
```
⛑ pезультаты будут в папке `.coverage-html`

Статический анализ кода Phan (_static analyzer for PHP_)

```shell
docker-compose run --rm php vendor/bin/phan
```

Можно работать в shell оболочке в docker контейнере:
```shell
docker-compose run --rm php sh
```
