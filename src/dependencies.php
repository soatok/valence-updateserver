<?php

use ParagonIE\CSPBuilder\CSPBuilder;
use ParagonIE\EasyDB\Factory;
use ParagonIE\Quill\Quill;
use ParagonIE\Sapient\CryptographyKeys\{
    SigningPublicKey,
    SigningSecretKey
};
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

    $container['quill'] = function (Container $c) {
        $settings = $c->get('settings')['quill'];
        /** @var SigningPublicKey $pubKey */
        $pubKey = $settings['server-public-key'];
        if (is_string($pubKey)) {
            $pubKey = new SigningPublicKey($pubKey);
        }
        /** @var SigningSecretKey $secKey */
        $secKey = $settings['client-secret-key'];
        if (is_string($secKey)) {
            $secKey = new SigningSecretKey($secKey);
        }

        return new Quill(
            $settings['url'] ?? '',
            $settings['client-id'] ?? '',
            $pubKey,
            $secKey
        );
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
