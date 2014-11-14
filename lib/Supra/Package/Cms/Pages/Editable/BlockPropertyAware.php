<?php

namespace Supra\Package\Cms\Pages\Editable;

use Supra\Package\Cms\Entity\BlockProperty;

/**
 * Block property aware Editable item interface.
 */
interface BlockPropertyAware
{
	public function setBlockProperty(BlockProperty $blockProperty);
}