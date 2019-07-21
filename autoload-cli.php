<?php
require __DIR__ . '/vendor/autoload.php';
define('APP_ROOT', __DIR__);

// Instantiate the app
$settings = require __DIR__ . '/src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
$dependencies = require __DIR__ . '/src/dependencies.php';
$dependencies($app);

/** @var \Slim\Container $container */
$container = $app->getContainer();

/** @var \ParagonIE\EasyDB\EasyDB $db */
$db = $container['db'];

/**
 * @param string $text
 * @return string
 */
function prompt(string $text = ''): string
{
    static $fp = null;
    if ($fp === null) {
        $fp = \fopen('php://stdin', 'r');
    }
    echo $text;
    return \substr(\fgets($fp), 0, -1);
}

/**
 * @param string $text
 * @return string
 * @throws Exception
 */
function silentPrompt($text = "Enter Password:")
{
    if (\preg_match('/^win/i', PHP_OS)) {
        $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
        file_put_contents(
            $vbscript,
            'wscript.echo(InputBox("'. \addslashes($text) . '", "", "password here"))'
        );
        $command = "cscript //nologo " . \escapeshellarg($vbscript);
        $password = \rtrim(
            \shell_exec($command)
        );
        \unlink($vbscript);
        return $password;
    } else {
        $command = "/usr/bin/env bash -c 'echo OK'";
        if (\rtrim(\shell_exec($command)) !== 'OK') {
            throw new \Exception("Can't invoke bash");
        }
        $command = "/usr/bin/env bash -c 'read -s -p \"". addslashes($text). "\" mypassword && echo \$mypassword'";
        $password = \rtrim(\shell_exec($command));
        echo "\n";
        return $password;
    }
}
