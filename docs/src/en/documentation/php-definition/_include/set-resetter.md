A value used for resetting an object's state.

The `setResetter()` method is defined by the `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionResetterSetterInterface` interface.

```php
DiDefinitionResetterSetterInterface::setResetter(
    callable|false|string $resetter
)
```

> [!TIP]
> See the detailed information in the section [“Resetting object state in long-running processes”](/en/documentation/object-resetters/index.md).
