<?php
declare(strict_types=1);
namespace Soatok\Valence\Filter;

use ParagonIE\Ionizer\Filter\StringFilter;
use ParagonIE\Ionizer\InputFilterContainer;

/**
 * Class PublicKeyAddFilter
 * @package Soatok\Valence\Filter
 */
class PublicKeyAddFilter extends InputFilterContainer
{
    public function __construct()
    {
        $this->addFilter('publickey', new StringFilter());
    }
}
