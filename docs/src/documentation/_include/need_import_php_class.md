
> [!IMPORTANT]
> Явно не объявленные в контейнере PHP классы будут проигнорированы при поиске тега, поэтому необходимо обеспечить доступность PHP классов в контейнере используя:
> - импорт PHP-классов – [метод `DiContainerBuilder::import()`](../container-builder/index.md#dicontainerbuilder-import)
> - файлы конфигураций - [метод `DiContainerBuilder::load()`](../container-builder/index.md#load-definitions-from-file)
>
