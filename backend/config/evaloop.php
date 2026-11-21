<?php

return [
    // No default: only the explicit development seeder consumes this secret.
    'seed_password' => env('EVALOOP_SEED_PASSWORD'),
    'lesson_attachment_max_kb' => (int) env('EVALOOP_LESSON_ATTACHMENT_MAX_KB', 10240),
];
