<?php

use GuzzleHttp\Client as Guzzle;
use ParagonIE\Certainty\RemoteFetch;
use ParagonIE\ConstantTime\{
    Base64UrlSafe,
    Binary,
    Hex
};
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

    $container['http'] = function (Container $c) {
        return new Guzzle(
            [
                'verify' => (new RemoteFetch(APP_ROOT . '/local/cacerts'))
                    ->getLatestBundle()
            ]
        );
    };

    $container['quill'] = function (Container $c) {
        $settings = $c->get('settings')['quill'];
        /** @var SigningPublicKey $pubKey */
        $pubKey = $settings['server-public-key'];
        if (empty($pubKey)) {
            return null;
        }
        if (is_string($pubKey)) {
            $len = Binary::safeStrlen($pubKey);
            if ($len === 64) {
                $pubKey = Hex::decode($pubKey);
            } elseif ($len === 44) {
                $pubKey = Base64UrlSafe::decode($pubKey);
            }
            $pubKey = new SigningPublicKey($pubKey);
        }
        /** @var SigningSecretKey $secKey */
        $secKey = $settings['client-secret-key'];
        if (empty($secKey)) {
            return null;
        }
        if (is_string($secKey)) {
            $len = Binary::safeStrlen($secKey);
            if ($len === 128) {
                $secKey = Hex::decode($secKey);
            } elseif ($len === 88) {
                $secKey = Base64UrlSafe::decode($secKey);
            }
            $secKey = new SigningSecretKey($secKey);
        }

        return new Quill(
            $settings['url'] ?? '',
            $settings['client-id'] ?? '',
            $pubKey,
            $secKey,
            $c['http']
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
