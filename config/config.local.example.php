<?php
// Copy this file to config.local.php and adjust it for your machine.
// config.local.php is git-ignored, so credentials never get committed.

return [
    'app' => [
        'env' => 'development',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,      // MAMP uses 8889
        'name' => 'campus_event_reminder',
        'user' => 'root',
        'pass' => '',        // MAMP uses "root"
    ],
];
