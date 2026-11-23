When declaring dependency injection for a specific parameter, use the name of that parameter as the array key within `$arguments`.

Parameters that do not receive arguments via `$arguments` will be injected by the container automatically, based on the configuration.