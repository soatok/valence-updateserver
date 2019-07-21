<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Slim\{
    App,
    Container
};
use Soatok\Valence\Middleware\PublisherOnlyMiddleware;

return function (App $app) {
    /** @var Container $container */
    $container = $app->getContainer();
    $pubOnly = new PublisherOnlyMiddleware($container);

    $app->get('/download/{project:[A-Za-z0-9\-_]+}/{file:[A-Za-z0-9\-_]+}', 'route.download');
    $app->any('/publickey[/{action:[A-Za-z0-9\-_]+}]', 'route.publickey')->add($pubOnly);
    $app->any('/publish', 'route.publish')->add($pubOnly);
    $app->any('/publishers[/{name:[A-Za-z0-9\-_]+}]', 'route.publishers');
    $app->get('/updates/{project:[A-Za-z0-9\-_]+}[/{channel:[A-Za-z0-9\-_]+}]', 'route.updates');
    $app->get('/updates', 'route.updates');
    $app->get('/', 'route.index');
    $app->get('', 'route.index');

    $container['route.download'] = function (Container $c) {
        return new Download($c);
    };
    $container['route.index'] = function (Container $c) {
        return new Index($c);
    };
    $container['route.publickey'] = function (Container $c) {
        return new PublicKey($c);
    };
    $container['route.publish'] = function (Container $c) {
        return new Publish($c);
    };
    $container['route.publishers'] = function (Container $c) {
        return new Publishers($c);
    };
    $container['route.updates'] = function (Container $c) {
        return new Updates($c);
    };
};
