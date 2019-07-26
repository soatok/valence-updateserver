<?php
declare(strict_types=1);
namespace Soatok\Valence\Filter;

use ParagonIE\Ionizer\Filter\StringFilter;
use ParagonIE\Ionizer\InputFilterContainer;

/**
 * Class PublishFilter
 * @package Soatok\Valence\Filter
 */
class PublishFilter extends InputFilterContainer
{
    public function __construct()
    {
        $this->addFilter('channel', (new StringFilter())->setDefault('public'))
            ->addFilter('project', new StringFilter())
            ->addFilter('publickey', new StringFilter())
            ->addFilter('signature', new StringFilter())
            ->addFilter('version', new StringFilter());
    }
}
