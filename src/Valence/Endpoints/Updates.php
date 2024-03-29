<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Interop\Container\Exception\ContainerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use Soatok\AnthroKit\Endpoint;
use Soatok\Valence\AccessTrait;
use Soatok\Valence\Splices\Projects;

/**
 * Class Updates
 * @package Soatok\Valence\Endpoints
 */
class Updates extends Endpoint
{
    use AccessTrait;

    /** @var Projects $projects */
    private $projects;

    /**
     * Download constructor.
     *
     * @param Container $container
     * @throws ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->projects = $this->splice('Projects');
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param array $routerParams
     * @return ResponseInterface
     * @throws \SodiumException
     */
    public function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface {
        // Validate input
        $projectName = $routerParams['project'] ?? '';
        $channel = $routerParams['channel'] ?? '';
        if (empty($projectName)) {
            return $this->json([
                'info' => 'You want to visit /project/{name}',
                'projects' => $this->projects->listProjects()
            ]);
        }

        // Only show updates for the channels you have access to:
        $updates = $this->projects->listUpdates($projectName, $channel);
        $publish = [];
        $cache = [];
        foreach ($updates as $key => $update) {
            // If it's public, we don't need to examine it further.
            $found = $update['channel_value'] === 0;
            if (!$found) {
                if (!$request->hasHeader('Valence-Access')) {
                    continue;
                }
                // Try up to 8 tokens, if passed along
                for ($i = 0; $i < 8; ++$i) {
                    $token = $this->extractAccessToken($request, $i);
                    if (!$token) {
                        // This isn't a valid token.
                        continue;
                    }
                    if (isset($cache[$update['project']])) {
                        $access = $cache[$update['project']];
                    } else {
                        $access = (int) $this->accessCheck($token, (int)$update['project']);
                        $cache[$update['project']] = $access;
                    }
                    if ($access < $update['channel_value']) {
                        // This token doesn't grant access to this channel.
                        continue;
                    }
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $publish[] = [
                    'channel' => $update['channel'],
                    'version' => $update['version'],
                    'created' => (new \DateTime($update['created']))
                        ->format(\DateTime::ISO8601),
                    'publisher' => $update['publisher'],
                    'project' => $update['project_name'],
                    'chronicle' => $update['chronicle_publish'],
                    'url' => '/download/' . $update['project_name'] . '/' . $update['publicid']
                ];
            }
        }

        return $this->json([
            'updates' => $publish
        ]);
    }
}
