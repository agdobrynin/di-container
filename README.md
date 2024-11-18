# DiContainer

Kaspi/di-container — это легковесный контейнер внедрения зависимостей для PHP >= 8.0 с автоматическим связыванием.

## Установка

```shell
composer require kaspi/di-container
```
### Особенности

- **Autowiring** - контейнер автоматически создаёт и внедряет зависимости.
- Поддержка "**zero configuration for dependency injection**" - когда ненужно объявлять зависимость если класс существуют и может быть запрошен по "PSR-4 auto loading"
- Поддержка **Php-атрибутов** для конфигурирования сервисов в контейнере.

## Быстрый старт
```php
// определение контейнера с настройкой "zero configuration for dependency inject"
// когда ненужно объявлять зависимость если класс существуют
// и может быть запрошен по "PSR-4 auto loading"
$container = (new \Kaspi\DiContainer\DiContainerFactory())
    ->make([
        \Kaspi\DiContainer\diAutowire(App\Services\Mail::class)
            ->addArgument('transport', 'sendmail')
    ]);
```

```php
namespace App\Services;

class Mail {
    public function __construct(private string $transport) {}
    
    public function envelop() {
        // ...
    }
    
    public function send(): bool {
        // ...
    }
}
```

```php
namespace App\Models;

class Post {
    public string $title;
    // ...
}
```

```php
// определение класса
namespace App\Controllers;

use App\Services\Mail;
use App\Models\Post;

class  PostController {
    public function __construct(private Mail $mail) {}
    
    public function send(Post $post): bool {
        $this->mail->envelop()
            ->subject('Publication success')
            ->body('Post <'.$post->title.'> was published.');
        return $this->mail->send();
    }
}
```
```php
$post = new App\Models\Post();
$post->title = 'Publication about DiContainer';

// ...

// получить класс PostController с внедренным сервисом Mail и выполнить метод "send"
$postController = $container->get(App\Controllers\PostController::class);
$postController->send($post);
```
Фактически `DiContainer` выполнит следующие действия для `App\Controllers\PostController`:

```php
$post = new App\Controllers\PostController(
    new App\Services\Mail('sendmail')
);
```
> контейнер "пытается" самостоятельно определить запрашиваемую зависимость - является ли это классом или callable типом.

🦄 Примеры использования пакета kaspi/di-container в [репозитории](https://github.com/agdobrynin/di-container-examples)

### Конфигурирование DiContainer

Для конфигурирования параметров используется класс:
`Kaspi\DiContainer\DiContainerConfig::class` который имплементируют интерфейс `Kaspi\DiContainer\Interfaces\DiContainerConfigInterface`

```php
$diConfig = new \Kaspi\DiContainer\DiContainerConfig(
    // Использовать автоматическое разрешение аргументов
    // сервисов-классов или методов-классов или функций.
    useAutowire: true,
    // Ненужно объявлять каждую зависимость.
    // Если класс или функция или интерфейс существуют -
    // то он может быть запрошен по "PSR-4 autoloading".
    useZeroConfigurationDefinition: true,
    // Использовать Php-атрибуты для объявления зависимостей.
    useAttribute: true,
    // Сервис (объект) будет создаваться заново при разрешении зависимости
    // если знание true, то объект будет создан как Singleton.
    isSingletonServiceDefault: false,
    // Строка (символ) определяющий шаблон как ссылку другой контейнер
    referenceContainerSymbol: '@',
);
// передать настройки в контейнер
$container = new \Kaspi\DiContainer\DiContainer(config: $diConfig);
```
Или использовать фабрику с настроенными по умолчанию параметрами:
```php
$container = (new \Kaspi\DiContainer\DiContainerFactory())->make(definitions: []);
```

⚙ При попытке разрешить зависимость через метод `get` или аргумент конструктора, или метода:

- `$container->get(Psr\Container\ContainerInterface::class);`
- `$container->get(Kaspi\DiContainer\DiContainer::class);`
- `$container->get(Kaspi\DiContainer\Interfaces\DiContainerInterface::class);`

| будет получен текущий class `Kaspi\DiContainer\DiContainer::class`

```php
function testFunc(\Psr\Container\ContainerInterface $c) {
    return $c;
}

$container = (new \Kaspi\DiContainer\DiContainerFactory())->make();
$container->call('testFunc') instanceof \Kaspi\DiContainer\DiContainer; // true
```
```php
class TestClass {
    public function __construct(
        public \Psr\Container\ContainerInterface $container
    ) {}
}

$container = (new \Kaspi\DiContainer\DiContainerFactory())->make();
$container->get(TestClass::class)->container instanceof \Kaspi\DiContainer\DiContainer; // true
```

### Подробное описание конфигурирования и использования

* [DiContainer с конфигурированием на основе php-определений](https://github.com/agdobrynin/di-container/blob/main/docs/01-php-definition.md).
* [DiContainer c конфигурированием через PHP атрибуты](https://github.com/agdobrynin/di-container/blob/main/docs/02-attribute-definition.md).
* [DiContainer::call](https://github.com/agdobrynin/di-container/blob/main/docs/03-call-method.md) для вызова чистых `callable` типов и дополнительных определений. 

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
