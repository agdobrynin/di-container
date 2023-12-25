### Kaspi/DiContainer

Kaspi/DiContainer легковесный — это контейнер внедрения зависимостей для PHP >= 8.2 с автоматическим связыванием зависимостей.

#### Установка

Перед использованием установить через composer

```shell
composer require kaspi/di-container
```

#### Использование

##### Тесты
Прогнать тесты без подсчета покрытия кода
```shell
composer test
```
Запуск тестов с проверкой покрытия кода тестами
```shell
./vendor/bin/phpunit
```

##### Code style
Для приведения кода к стандартам используем php-cs-fixer который объявлен 
в dev зависимости composer-а

```shell
composer fixer
``` 
