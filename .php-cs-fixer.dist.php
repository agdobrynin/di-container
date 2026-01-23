<?php

declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'tests/FinderFullyQualifiedClassName/Fixtures/Error',
        'tests/FinderFullyQualifiedClassName/Fixtures',
        'tests/_var',
        'tests/FinderClosureCode/Fixture/',
        'tests/DefinitionsLoader/Fixtures/PhpFileCannotParse/',
    ])
    ->notPath([
        'src/DiContainer/Compiler/template.php'
    ])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_to_comment' => [
            'ignored_tags' => ['phpstan-var', 'phpstan-param'],
        ],
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope' => 'all',
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
