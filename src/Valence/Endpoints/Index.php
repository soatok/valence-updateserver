<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soatok\AnthroKit\Endpoint;

/**
 * Class Index
 * @package Soatok\Valence\Endpoints
 */
class Index extends Endpoint
{
    public function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface {
        return $this->json([
            'api' => [
                [
                    'uri' => '/publishers',
                    'auth_needed' => false,
                    'description' => 'Returns a list of publishers',
                ], [
                    'uri' => '/publishers/{name}',
                    'auth_needed' => false,
                    'description' => 'Returns a list of projects owned by this publisher',
                ], [
                    'uri' => '/updates/{project}',
                    'auth_needed' => false,
                    'description' => 'Returns a list of recent updates for this project',
                ], [
                    'uri' => '/updates/{project}/{channel}',
                    'description' => 'Returns a list of recent updates for this project, within a given channel',
                ], [
                    'uri' => '/download/{project}/{file}',
                    'description' => 'Downloads a file. Some channels may require a special token to access.',
                ], [
                    'uri' => '/publickey/add',
                    'auth_needed' => true,
                    'post_params' => [
                        'name' => 'publickey',
                        'type' => 'string',
                        'description' => 'Public key to add to your current publisher account'
                    ]
                ], [
                    'uri' => '/publickey/revoke',
                    'auth_needed' => true,
                    'post_params' => [
                        'name' => 'publickey',
                        'type' => 'string',
                        'description' => 'Public key to remove from your current publisher account'
                    ],
                ], [
                    'uri' => '/publish',
                    'auth_needed' => true,
                    'file_params' => ['update'],
                    'post_params' => [
                        [
                            'name' => 'channel',
                            'type' => 'string',
                            'description' => 'Name of the channel'
                        ], [
                            'name' => 'project',
                            'type' => 'string',
                            'description' => 'Name of the project'
                        ], [
                            'name' => 'publickey',
                            'type' => 'string',
                            'description' => 'Public key used for this signature'
                        ], [
                            'name' => 'signature',
                            'type' => 'string',
                            'description' => 'Signature of the attached file'
                        ]
                    ],
                    'description' => 'Publish a new update',
                ]
            ]
        ]);
    }
}
