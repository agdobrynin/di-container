# DiFactory

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\DiFactory` внедряет зависимость контейнера через паттерн «фабрика» [^FactoryPattern].

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\DiFactory::__construct(
    string|array $definition,
    ?bool $isSingleton = null,
    array $arguments = []
)
```

Параметры:
- `$definition` – представление php класса и метода фабрики.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).
- `$arguments` – предать аргументы для метода фабрики.

Подробное описание в разделе документации [«Использование фабричных методов для разрешения зависимостей»](../07-factory.md).

## Пример использования фабрики для конфигурирования PHP класса { #example-do-config-php-class }

<!--@include: ../_include/term_notes.md-->
