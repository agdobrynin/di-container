
> [!IMPORTANT]
> PHP classes that have not been explicitly registered in the container will be ignored during tag-based lookups; therefore, you must ensure that these classes are added to the container using one of the following methods:
> - importing PHP classes — via the [`DiContainerBuilder::import()`](../container-builder/methods/import.md) method.
> - using configuration files — via the [`DiContainerBuilder::load()`](../container-builder/methods/load-definitions-from-file.md) method.
