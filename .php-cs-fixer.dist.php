<?php

declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'tests/FinderClass/Fixtures/Error',
    ])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'php_unit_test_class_requires_covers' => false,
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope' => 'all',
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
