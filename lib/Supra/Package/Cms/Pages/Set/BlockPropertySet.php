<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\Block;

/**
 * Set of page block properties
 */
class BlockPropertySet extends AbstractSet
{
	/**
	 * @TODO: maybe optimize by grouping the properties once
	 *
	 * @param Block $block
	 * @return BlockPropertySet
	 */
	public function getBlockPropertySet(Block $block)
	{
		$blockPropertySet = new BlockPropertySet();
		
		/* @var $blockProperty \Supra\Package\Cms\Entity\BlockProperty */
		foreach ($this as $blockProperty) {
			if ($blockProperty->getBlock()->equals($block)) {
				$blockPropertySet->append($blockProperty);
			}
		}
		
		return $blockPropertySet;
	}
}
