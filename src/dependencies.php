<?php

use ParagonIE\CSPBuilder\CSPBuilder;
use ParagonIE\EasyDB\Factory;
use Slim\App;
use Slim\Container;

return function (App $app) {
    $container = $app->getContainer();

    $container['csp'] = function (Container $c): CSPBuilder {
        if (file_exists(APP_ROOT . '/local/content_security_policy.json')) {
            return CSPBuilder::fromFile(APP_ROOT . '/local/content_security_policy.json');
        }
        return CSPBuilder::fromFile(__DIR__ . '/content_security_policy.json');
    };

    $container['db'] = function (Container $c) {
        $settings = $c->get('settings')['database'];
        return Factory::create(
            $settings['dsn'],
            $settings['username'],
            $settings['password'],
            $settings['options'] ?? []
        );
    };
    $container['database'] = $container['db'];
};
