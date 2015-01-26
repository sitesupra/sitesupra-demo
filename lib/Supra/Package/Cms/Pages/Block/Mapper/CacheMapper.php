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

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Pages\Response\ResponseContext;

class CacheMapper extends Mapper
{
	protected $lifetime = 3600;

	/**
	 * @return int
	 */
	public function getLifetime()
	{
		return $this->lifetime;
	}

	/**
	 * @param int $lifetime
	 */
	public function setLifetime($lifetime)
	{
		$this->lifetime = $lifetime;
	}

	public function getCacheKey(Localization $localization, Block $block, ResponseContext $context = null)
	{
		return sprintf('supra_block_cache_%s_%s_%s',
			$localization->getId(),
			$block->getId(),
			$this->getContextKey($context)
			);
	}

	protected function getContextKey(ResponseContext $context = null)
	{
		if (!$context) {
			return 'no_context';
		}

		$cacheParts = array();

		$values = $context->getAllValues();

		ksort($values);

		foreach ($values as $value) {
			$cacheParts[] = $value;
		}

		return implode('_', $cacheParts);
	}
}
