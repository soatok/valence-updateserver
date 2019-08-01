<?php
$default = [
    'displayErrorDetails' => true, // set to false in production
    'addContentLengthHeader' => false, // Allow the web server to send the content-length header

    'quill' => [
        'url' => '',
        'client-id' => '',
        'server-public-key' => '',
        'client-secret-key' => ''
    ],

    // Renderer settings
    'renderer' => [
        'template_path' => __DIR__ . '/../templates/',
    ],

    // Monolog settings
    'logger' => [
        'name' => 'slim-app',
        'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
        'level' => \Monolog\Logger::DEBUG,
    ],
];


if (is_readable(APP_ROOT . '/local/settings.php')) {
    $local = require_once APP_ROOT . '/local/settings.php';
    return [
        'settings' => $local + $default
    ];
}
return [
    'settings' => $default
];
