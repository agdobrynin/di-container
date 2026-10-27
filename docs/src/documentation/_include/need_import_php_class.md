
> [!IMPORTANT]
> Явно не объявленные в контейнере PHP классы будут проигнорированы при поиске тега, поэтому необходимо обеспечить доступность PHP классов в контейнере используя:
> - импорт PHP-классов – [метод `DiContainerBuilder::import()`](../container-builder/methods/import.md)
> - файлы конфигураций - [метод `DiContainerBuilder::load()`](../container-builder/methods/load-definitions-from-file.md)
>
