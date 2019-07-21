<?php
declare(strict_types=1);
namespace Soatok\Valence\Splices;

use Soatok\AnthroKit\Splice;

/**
 * Class Publishers
 * @package Soatok\Valence\Splices
 */
class Publishers extends Splice
{
    /**
     * @param int $publisherId
     * @param int $project
     * @return bool
     */
    public function publisherOwns(int $publisherId, int $project): bool
    {
        $expect = $this->db->cell(
            "SELECT owner FROM valence_projects WHERE projectid = ?",
            $project
        );
        if ($expect) {
            return false;
        }
        return $publisherId === $expect;
    }

    /**
     * @param string $publicKey
     * @param int $publisherId
     * @return bool
     */
    public function publicKeyBelongsTo(string $publicKey, int $publisherId): bool
    {
        return $this->db->exists(
            "SELECT count(*)
            FROM valence_publisher_publickeys
            WHERE publickey = ? AND publisher = ?
            ",
            $publicKey,
            $publisherId
        );
    }

    /**
     * @param string $name
     * @return array
     * @throws \Exception
     */
    public function getProjects(string $name): array
    {
        $publisher = $this->db->cell(
            "SELECT publisherid FROM valence_publishers WHERE name = ?",
            $name
        );
        if (!$publisher) {
            throw new \Exception('Invalid publisher');
        }
        $projects = $this->db->col(
            "SELECT name FROM valence_projects WHERE owner = ?",
            0,
            $publisher
        );
        if (!$projects) {
            return [];
        }
        return $projects;
    }

    /**
     * @return array
     */
    public function listPublishers(): array
    {
        $publishers = $this->db->col(
            "SELECT name FROM valence_publishers ORDER BY name ASC",
            0
        );
        if (!$publishers) {
            return [];
        }
        return $publishers;
    }
}
