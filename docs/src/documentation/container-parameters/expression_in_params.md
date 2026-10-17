# Ссылки на имена других «параметров контейнера» { #title }

## Обзор

Чтобы указать ссылку на другой ранее добавленный «параметр контейнера», необходимо имя параметра указать между символами `{` и `}`.

Значение `'{emails.admin}'` будет интерпретировано как получение значения параметра контейнера с именем `'emails.admin'`.

Для строковых значений параметров поддерживается синтаксис объединения, склеивания, значений типа `string` и `number` <sup>[1](#tip)</sup>.

## Пример использования ссылок в «параметрах контейнера» { #example }

Конфигурация «параметров контейнера»:

::: code-group

```php [app_params.php]
// file: /app/config/app_params.php
return [
    'storage' => '/var/storage',
    'emails.admin' => 'admin@example.com',
    'emails.manager' => 'manager@example.com',
];
```

```php [dev_params.php]
// file: /app/config/dev_params.php
return [
    'storage.images' => '{storage}/images',
    'emails.as_string' => '{emails.admin}, {emails.manager}',
    'emails.as_array' => [
        '{emails.admin}',
        '{emails.manager}',
    ],
];
```

::: 

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters(
        '/app/config/app_params.php',
        '/app/config/dev_params.php',
    )
    ->build();

$parameters = $container->parameters();

var_dump($parameters->get('storage.images'));
// string(19) "/var/storage/images"

var_dump($parameters->get('emails.as_string'));
// string(38) "admin@example.com, manager@example.com"

var_dump($parameters->get('emails.as_array'));
// array(2) {
//   [0]=>
//   string(17) "admin@example.com"
//   [1]=>
//   string(19) "manager@example.com"
// }
```

<span id="tip"/>

> [!TIP]
> При объединении ссылок в строковом параметре допускается объединение
> числовых и строковых значений параметров
> ```php
>   return [
>       'api.host' => 'api.example.com',
>       'api.host_port' => 8080,
>       'api.url' => '{api.host}:{api.host_port}',
>   ];
> ```
> Значение параметра `'api.url'` будет приведено к строке `'api.example.com:8080'`.


## Экранирование символа `{` { #escape-symbol }

Символ `{` используется как указатель на имя другого параметра. Если символ `{` является частью значения его необходимо экранировать, добавив еще один символ `{` чтобы избежать интерпретации его как ссылки на имя параметра.

Конфигурация «параметров контейнера»:

```php
// file: /app/config/escaped_parameters.php
return [
     'parameter.escaped' => 'foo{{bar}baz',
];
```
Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/escaped_parameters.php')
    ->build();

$parameters = $container->parameters();

var_dump($parameters->get('parameter.escaped'));
// string(11) "foo{bar}baz"
```
