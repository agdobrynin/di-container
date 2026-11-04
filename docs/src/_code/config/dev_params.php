<?php

// file: /app/config/dev_params.php //[!code highlight]

return [
    'storage.images' => '{storage}/images',
    'emails.as_string' => '{emails.admin}, {emails.manager}',
    'emails.as_array' => [
        '{emails.admin}',
        '{emails.manager}',
    ],
];
