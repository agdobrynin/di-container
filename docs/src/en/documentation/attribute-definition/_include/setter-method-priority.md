The container injects dependencies via setter methods marked with an attribute, following the order in which they are declared in the PHP class.

If necessary, you can change the order of dependency injection [using the `Kaspi\DiContainer\Attributes\SetupPriority` attribute](setup-priority.md).