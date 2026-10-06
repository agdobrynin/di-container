Для параметров не переданных через `$arguments` контейнер попытается внедрить зависимости самостоятельно на основе конфигурации, включая [использование PHP атрибутов](../index.md#attributes).

Для передачи неполного списка аргументов `$arguments` указывайте в качестве ключа массива имя параметра.


::: details Для аргументов в параметре `$arguments` можно использовать классы других определений.

- `Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire` – php класс.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionCallable` – вызываемый тип `callable`.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionFactory` – [фабрика](../../07-factory.md) для внедрения зависимости.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionGet` – ссылка на идентификатор контейнера.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionParameter` – [параметр контейнера](../../09-container-parameters.md).
- `Kaspi\DiContainer\DiDefinition\DiDefinitionParameterRutime` – [параметр контейнера времени выполнения](../../09-container-parameters.md).
- `Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure` – внедрение зависимости через «ленивую загрузку».
- `Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime` – объект времени выполнения.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs` – [коллекция по тегу](../../05-tags.md).

:::
