<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
