## Аргументы для метода фабрики.

Для особых сценариев настройки фабрики можно установить аргументы для параметров метода-фабрики:

- Через метод в хелпер функции `\Kaspi\DiContainer\diFactory::bingArguments()`:
    ```php
    bindArguments(mixed ...$argument)
    ``` 
  > для передачи неполного списка аргументов используйте именованные аргументы.

- Через параметр `$arguments` у php атрибута `\Kaspi\DiContainer\Attributes\DiFactory` :
    ```php
    #[DiFactory(string|array $definition, ?bool $isSingleton = null, array $arguments = [])]
    ```
  > для передачи неполного списка аргументов используйте в качестве ключа в массиве `$arguments` имя параметра в методе фабрике.

> [!TIP]
> Для указания как разрешать скалярные типы зависимостей в аргументах рекомендуется использовать «[параметры контейнера](../container-parameters/index.md)».