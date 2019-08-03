<?php
declare(strict_types=1);
namespace Soatok\Valence;

use ParagonIE\ConstantTime\Binary;
use ParagonIE\EasyDB\EasyDB;
use Psr\Http\Message\RequestInterface;
use Slim\Container;

/**
 * Trait AccessTrait
 * @package Soatok\Valence
 * @property Container $container
 */
trait AccessTrait
{
    /**
     * Performs two checks:
     *
     * 1. Did you access this from an Endpoint wrapped in the appropriate
     *    Middleware? (This ensures you're a valid publisher.)
     * 2. Does your publisher account own this project?
     *
     * @param int $project
     * @return bool
     */
    public function publisherCheck(int $project): bool
    {
        $publisher = (int) $this->container['active_publisher_id'];
        if (empty($publisher)) {
            return false;
        }

        /** @var EasyDB $db */
        $db = $this->container['db'];
        $expected = (int) $db->cell(
            "SELECT owner FROM valence_projects WHERE projectid = ?",
            $project
        );
        return $expected === $publisher;
    }

    /**
     * Extracts the access token from the Valence-Access header.
     *
     * @param RequestInterface $request
     * @param int $offset
     * @return string|null
     */
    public function extractAccessToken(RequestInterface $request, int $offset = 0): ?string
    {
        /** @var EasyDB $db */
        $db = $this->container['db'];
        $headers = $request->getHeader('Valence-Access');
        if ($offset >= count($headers)) {
            return null;
        }
        $headers = array_slice($headers, $offset);
        foreach ($headers as $header) {
            if ($db->exists(
                "SELECT count(*) FROM valence_access WHERE selector = ?",
                Binary::safeSubstr($header, 0, TokenHash::SELECTOR_LENGTH)
            )) {
                return $header;
            }
        }
        return null;
    }

    /**
     * Returns the max channel value for the given access token
     *
     * @param string $token
     * @param int $project
     * @return int|null
     * @throws \SodiumException
     */
    public function accessCheck(string $token, int $project): ?int
    {
        /** @var EasyDB $db */
        $db = $this->container['db'];

        [$selector, $validator] = TokenHash::split($token);
        $row = $db->row(
            "SELECT
                 va.selector, va.validator, vpa.channelmax
             FROM valence_access va
             JOIN valence_project_access vpa ON va.accessid = vpa.accessid
             WHERE selector = ? AND vpa.projectid = ?",
            $selector,
            $project
        );
        if (empty($row)) {
            return null;
        }

        if (!TokenHash::verify(
            $row['validator'],
            $selector,
            $validator
        )) {
            return null;
        }

        return (int) $row['channelmax'];
    }
}
