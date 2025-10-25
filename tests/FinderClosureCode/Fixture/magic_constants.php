<?php
declare(strict_types=1);

namespace Tests\FinderClosureCode\Fixture;

return [
    'fn1' => static function (): array {
        return [
            'T_DIR' => __DIR__,
            'T_FILE' => __FILE__,
            'T_LINE' => __LINE__,
            'T_NS_C' => __NAMESPACE__,
        ];
    },
];
