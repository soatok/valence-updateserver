<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use ParagonIE\Sapient\Exception\{
    HeaderMissingException,
    InvalidMessageException
};
use ParagonIE\Quill\Quill;

/**
 * Trait ChronicleTrait
 * @package Soatok\Valence\Endpoints
 * @property Quill $quill
 */
trait ChronicleTrait
{
    /**
     * Write data to the Chronicle.
     *
     * @param array $data
     * @return array
     * @throws HeaderMissingException
     * @throws InvalidMessageException
     */
    public function writeAndParseChronicle(array $data): array
    {
        $response = $this->quill->write(json_encode($data));
        $json = (string) $response->getBody();
        return json_decode($json, true);
    }
}
