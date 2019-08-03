<?php
declare(strict_types=1);
namespace Soatok\Valence;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\EasyDB\EasyDB;
use Slim\Container;

/**
 * Class TokenHash
 * @package Soatok\Valence
 */
class TokenHash
{
    const SELECTOR_LENGTH = 24;

    const DOMAIN_SEPARATOR_MSG = "Valence\x01\x01\x01\x01\x01\x01\x01\x01";
    const DOMAIN_SEPARATOR_KEY = "\x80\x80\x80\x80\x80\x80\x80\x80Valence";

    /**
     * Verifies that you have a valid publisher token. Doesn't validate
     * access controls beyond this.
     *
     * Stores 'active_publisher_id' in the Slim container for use in further
     * access controls logic.
     *
     * @param Container $container
     * @param string $whole
     * @return bool
     * @throws \SodiumException
     */
    public static function publisherAuth(Container $container, string $whole): bool
    {
        /** @var EasyDB $db */
        $db = $container['db'];
        [$selector, $validator] = self::split($whole);
        $row = self::fetch($db, $selector);
        if (empty($row)) {
            return false;
        }
        if (!self::verify(
            $row['validator'],
            $selector,
            $validator
        )) {
            return false;
        }
        $container['active_publisher_id'] = $row['publisher'];
        return true;
    }

    /**
     * @param EasyDB $db
     * @param string $selector
     * @return array
     */
    public static function fetch(EasyDB $db, string $selector): array
    {
        $row = $db->row(
            "SELECT * FROM valence_publisher_tokens WHERE selector = ?",
            $selector
        );
        if (!$row) {
            return [];
        }
        return $row;
    }

    /**
     * Split the given token into the selector (can be leaked through
     * timing information in database lookups) and the validator
     * (which does not leak through timing information).
     *
     * @param string $whole
     * @return array<int, string>
     */
    public static function split(string $whole): array
    {
        $selector = Binary::safeSubstr($whole, 0, self::SELECTOR_LENGTH);
        $validator = Binary::safeSubstr($whole, self::SELECTOR_LENGTH);
        return [$selector, $validator];
    }

    /**
     * Domain-separated hash of the selector and validator.
     *
     * @param string $selector
     * @param string $validatorSecret
     * @return string
     * @throws \SodiumException
     */
    public static function hash(
        string $selector,
        string $validatorSecret
    ): string {
        return Base64UrlSafe::encodeUnpadded(
            sodium_crypto_generichash(
                self::DOMAIN_SEPARATOR_MSG . $selector,
                sodium_crypto_generichash(
                    self::DOMAIN_SEPARATOR_KEY . $validatorSecret
                ),
                33
            )
        );
    }

    /**
     * Verifies that a selector + validator pair matches the stored
     * validator hash.
     *
     * @param string $stored
     * @param string $selector
     * @param string $validatorSecret
     * @return bool
     * @throws \SodiumException
     */
    public static function verify(
        string $stored,
        string $selector,
        string $validatorSecret
    ): bool {
        $calc = self::hash($selector, $validatorSecret);
        return hash_equals($calc, $stored);
    }
}
