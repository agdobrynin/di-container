::: details В аргументах можно использовать классы других определений контейнера.

- `Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire` – PHP-класс.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionCallable` – внедряет зависимость через вызов `callable` типа.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionFactory` – [фабрика](/documentation/factories/index.md) для внедрения зависимости.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionGet` – внедрение зависимостей посредством ссылки на идентификатор контейнера.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionParameter` – внедрение зависимости из [параметров контейнера](/documentation/container-parameters/index.md).
- `Kaspi\DiContainer\DiDefinition\DiDefinitionParameterRutime` – внедрение зависимости из [параметров контейнера времени выполнения](/documentation/container-parameters/inject_runtime_params.md).
- `Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure` – внедрение зависимости через «ленивую загрузку».
- `Kaspi\DiContainer\DiDefinition\DiDefinitionRuntime` – внедрение объекта времени выполнения.
- `Kaspi\DiContainer\DiDefinition\DiDefinitionTaggedAs` – внедрение зависимости - [коллекция по тегу](/documentation/tags/index.md).

:::
