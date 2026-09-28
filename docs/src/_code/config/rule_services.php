<?php
declare(strict_types=1);

// file: /app/config/rule_services.php //[!code highlight]

use App\Rules\{RuleA, RuleB, RuleC};

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(RuleA::class)
        ->bindTag('tags.lite-rules');// [!code ++]

    yield diAutowire(RuleB::class)
        ->bindTag('tags.other-rulers');//[!code highlight]

    yield diAutowire(RuleC::class)
        ->bindTag('tags.lite-rules');// [!code ++]
};
