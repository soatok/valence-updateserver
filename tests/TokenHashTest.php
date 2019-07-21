<?php
namespace Soatok\Valence\Tests;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\ConstantTime\Binary;
use PHPUnit\Framework\TestCase;
use Soatok\Valence\TokenHash;

/**
 * Class TokenHashTest
 * @package Soatok\Valence\Tests
 */
class TokenHashTest extends TestCase
{
    /**
     * @throws \SodiumException
     */
    public function testHash()
    {
        $token = Base64UrlSafe::encode(random_bytes(64));
        [$selector, $validator] = TokenHash::split($token);

        $hashed = TokenHash::hash($selector, $validator);
        $this->assertSame(TokenHash::SELECTOR_LENGTH, Binary::safeStrlen($selector));
        $this->assertTrue(TokenHash::verify($hashed, $selector, $validator), 'Correct token');

        $token = Base64UrlSafe::encode(random_bytes(64));
        [$selector, $validator] = TokenHash::split($token);
        $this->assertFalse(TokenHash::verify($hashed, $selector, $validator), 'Incorrect token');
    }
}
