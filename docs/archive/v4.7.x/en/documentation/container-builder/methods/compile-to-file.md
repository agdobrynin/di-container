---
outline: [2,4]
title: Container compilation
---
### Container compilation { #title }

To improve the performance of the dependency container, a compiler has been implemented that transforms the container configuration into ready-to-use PHP code and saves it to a file; as a result, the container loads instantly during subsequent executions, bypassing the configuration parsing stage.

### compileToFile()

The `compileToFile()` method declares the container's compilation parameters as PHP code.

Method signature:

```php
\Kaspi\DiContainer\DiContainerBuilder::compileToFile(
    string $outputDirectory,
    string $containerClass,
    int $permissionCompiledContainerFile = 0666,
    bool $isExclusiveLockFile = true,
    array $options = []
): static;
```

Parameters:
- `$outputDirectory` – the destination directory.
- `$containerClass` – the FQCN[^FQCN] of the compiled container.
- `$permissionCompiledContainerFile` – file permissions for the compiled container file.
- `$isExclusiveLockFile` – whether to use an exclusive file lock while writing the PHP code to the destination file.
- `$options` – additional [compiler settings](#compile-options).

> [!IMPORTANT]
> To ensure maximum performance, the file containing the container's PHP code is compiled only once; if the file already exists, no recompilation is necessary, and the ready-to-use PHP code for the container becomes immediately available to the application.
>
> When deploying new (modified) versions of a container in a production environment, you must delete the generated file (or the directory containing it) to ensure that the container is recompiled.

> [!NOTE]
> The filename for the compiled container is generated based on the `$outputDirectory` and `$containerClass` parameters. The resulting full filename consists of the destination directory (`$outputDirectory`) combined with the short class name from `$containerClass`, excluding any specified namespace.

Example of configuring container compilation:

```php
<?php
declare(strict_types=1);

use Kaspi\DiContainer\DiContainerBuilder;

$builder = new DiContainerBuilder();

// ...

$builder->compileToFile(//[!code ++]
    outputDirectory: '/app/var/container',//[!code ++]
    containerClass: 'App\Core\FooContainer',//[!code ++]
);//[!code ++]

$container = $builder->build();
```

> [!TIP]
> A PHP class `\App\Core\FooContainer` will be generated, and the ready-to-use PHP code for the container will be saved to the file `/app/var/container/FooContainer.php`.

> [!WARNING]
> The directory specified in the `$outputDirectory` parameter must exist and be both readable and writable.

### Additional compiler settings { #compile-options }

The compiler can receive a number of additional settings via the `$options` parameter of the `compileToFile()` method, provided as an associative array with predefined keys.

#### Forced recompilation { #force_rebuild }

The `'force_rebuild'` parameter is of type `bool` and defaults to `false`.

To ensure maximum performance, the file containing the container's PHP code is compiled once; if the file already exists, no further compilation is required, and the ready-to-use PHP code for the container becomes immediately available to the application.

Setting `'force_rebuild' => true` ensures that the container is recompiled every time it is built.

#### Exception handling at compile time { #invalid_behavior }

The `'invalid_behavior'` parameter accepts a value of type `\Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum`, with a default value of `InvalidBehaviorCompileEnum::ExceptionOnCompile`.

During container compilation, an exception may be thrown if a PHP class does not exist or if a container definition configuration is missing. The `'invalid_behavior'` parameter and its assigned value instruct the compiler on how to handle such exceptions during the compilation process:

1. Halt compilation and throw an exception with a message:
> `'invalid_behavior' => InvalidBehaviorCompileEnum::ExceptionOnCompile`

2. Do not halt container compilation; instead, incorporate the exception into the PHP code of the compiled container:
> `'invalid_behavior' => InvalidBehaviorCompileEnum::RuntimeContainerException`

#### Implementation of a container definition transformer { #di_definition_transformer }

The `'di_definition_transformer'` parameter accepts an object; its default value is an empty string.

This refers to a PHP class responsible for transforming container definitions into the PHP classes required by the compiler.

A developer can create a custom PHP class that implements the `\Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface` interface and pass an instance of this class to the `'di_definition_transformer'` parameter (e.g., `'di_definition_transformer' => $customTransformer`).

#### A collection of compiled container definitions { #compiled_entries }

The `'compiled_entries'` parameter accepts an object; its default value is an empty string.

This is a PHP class responsible for aggregating compiled container definitions into a collection.

A developer can create a custom PHP class that implements the `\Kaspi\DiContainer\Interfaces\Compiler\CompiledEntriesInterface` interface and pass an instance of this class to the `'compiled_entries'` parameter (e.g., `'compiled_entries' => $customCompiledEntries`).

<!--@include: ../../_include/term_notes.md-->
