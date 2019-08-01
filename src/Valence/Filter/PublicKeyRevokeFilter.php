<?php
declare(strict_types=1);
namespace Soatok\Valence\Filter;

use ParagonIE\Ionizer\Filter\StringFilter;
use ParagonIE\Ionizer\InputFilterContainer;

/**
 * Class PublicKeyRevokeFilter
 * @package Soatok\Valence\Filter
 */
class PublicKeyRevokeFilter extends InputFilterContainer
{
    public function __construct()
    {
        $this->addFilter('publickey', new StringFilter());
    }
}
