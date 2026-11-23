---
outline: [2,4]
title: Building the dependency container
---

### Building the dependency container { #title }

The combination of methods in the `\Kaspi\DiContainer\DiContainerBuilder` class allows for flexible configuration and construction of a dependency container.

The chain of build configuration calls must end with the `\Kaspi\DiContainer\DiContainerBuilder::build()` method to obtain the configured dependency container.

The assembled container will provide the standard `get()` and `has()` methods from the [PSR-11](https://www.php-fig.org/psr/psr-11/) specification,
the [`call()` method](../call/index.md), and an additional [`set()` method](set.md) for dynamically adding definitions to the container.

```php
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface as Call;
use Kaspi\DiContainer\Interfaces\DiContainerInterface as PsrContainer;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface as Setter;

/**
 * @var Call&PsrContainer&Setter $container 
 */
$container = (new DiContainerBuilder())
    ->build()
;
```

> [!IMPORTANT]
> The container configuration is validated within the `DiContainerBuilder::build()` method. If the configuration is found to be invalid, an exception that implements the `\Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface` interface will be thrown.

> [!IMPORTANT]
> [Methods for loading from configuration files](methods/load-definitions-from-file.md) follow a specific order of priority: the earlier a loading method is applied, the higher the priority given to the definitions it provides. Container identifiers[^ContainerId] used within these definitions must be recognizable within the context of the container being created.
>
> In some use cases, you may need to override previously declared definitions. To perform an override, use the [`DiContainerBuilder::addDefinitionsOverride()`](methods/load-definitions-from-collection.md#adddefinitionsoverride) and [`DiContainerBuilder::loadOverride()`](methods/load-definitions-from-file.md#loadoverride) methods.
>
> An exception will be thrown if a conflict arises in the container definition declarations.


### Container's assembly methods { #methods }

The `\Kaspi\DiContainer\DiContainerBuilder` class implements the `\Kaspi\DiContainer\Interfaces\DiContainerBuilderInterface` interface and provides methods for declaring container definitions, registering container parameters, creating contexts for configuration files, and compiling the container into ready-to-use PHP code, which is then saved to a file.

[Declaring container definitions from configuration files](methods/load-definitions-from-file.md):
- `load()` — declares definitions in the container based on configuration files.
- `loadOverride()` — declares definitions in the container based on configuration files, with the option to overwrite existing ones.

[Declaring container definitions via a collection](methods/load-definitions-from-collection.md):
- `addDefinitions()` — declares definitions in the container based on a collection.
- `addDefinitionsOverride()` — declares definitions in the container based on a collection, with the option to overwrite existing ones.

[Registering container parameters](methods/load-parameters.md):
- `loadParameters()` — registers container parameters from files.
- `addParameters()` — registers container parameters from a collection.
- `setParameter()` — registers a container parameter and its value.

[Registering PHP classes in the container based on the file system](methods/import.md):
- `import()` — registers PHP classes by scanning the file system.

[Container compilation](methods/compile-to-file.md):
- `compileToFile()` — sets the parameters for compiling the container into PHP code.

[Passing context for configuration files](methods/configuration-context.md):
- `addConfiguratorContexts()` — a set of contexts for configuration files.
- `setConfiguratorContext()` — a named context for configuration files.

### Using a container across different application environments { #build-container-env }

Application environments are named according to their purpose:
Local (**dev**) — for developers; test (**test**) — for quality assurance (QA) specialists;
production (**prod**) — for end users.

Do not use [container compilation](methods/compile-to-file.md) in the development (**dev**) environment; otherwise, any changes you make to the container configuration—such as modifications to application code via attributes, configuration files, etc.—will not be reflected. Container compilation into PHP code occurs only once—during the initial build of the container.

#### How to use compilation in different application environments { #how-to-build-container-env }

An example of isolating container build settings across different development environments:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;

use function getenv;

$builder = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src/')
    ->load(
        '/app/config/base_services.php',
        '/app/config/prod_services.php'
    )
;

// 🚩 environment for local application development
if ('dev' === getenv('APP_ENV')) {
    $builder->loadOverride('/app/config/dev_services.php');// [!code highlight]
}

// 🚩 Compile the container only when in the 'prod' environment (APP_ENV === 'prod')
if ('prod' === getenv('APP_ENV')) {
    $builder->compileToFile('/app/var/container', 'App\\Core\\FooContainer');// [!code highlight]
}

$container = $builder->build();
```

> [!TIP]
> When deploying a new or modified version of a container in a production environment, it is necessary to remove the previously created container file—for example, by executing a shell script, as shown in the example above:
> ```shell
>  rm /app/var/container/FooContainer.php
> ```
> After deleting the previously generated file `/app/var/container/FooContainer.php`, it is recommended to "warm up" the application so that the container is compiled upon the first request. Subsequent calls to the container within the code will then use this compiled container.

<!--@include: ../_include/term_notes.md-->
