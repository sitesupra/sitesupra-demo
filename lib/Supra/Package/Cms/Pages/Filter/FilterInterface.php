<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Package\Cms\Editable\Filter\FilterInterface as BaseFilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;

interface FilterInterface extends BaseFilterInterface
{
	public function setBlockProperty(BlockProperty $blockProperty);
}