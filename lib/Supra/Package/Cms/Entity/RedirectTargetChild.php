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

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Uri\Path;

/**
 * @Entity
 */
class RedirectTargetChild extends RedirectTargetPage
{
	const CHILD_POSITION_FIRST = 'first';
	const CHILD_POSITION_LAST = 'last';

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $childPosition;

	/**
	 * {@inheritDoc}
	 */
	public function getRedirectUrl()
	{
		$child = $this->getTargetChild();
		
		return $child ? $child->getPath()->format(Path::FORMAT_BOTH_DELIMITERS) : null;
	}

	/**
	 * @return Page | null
	 */
	public function getTargetPage()
	{
		$child = $this->getTargetChild();
		return $child ? $child->getMaster() : null;
	}

	/**
	 * @param string $childPosition
	 */
	public function setChildPosition($childPosition)
	{
		if (! ($childPosition === self::CHILD_POSITION_FIRST
				&& $childPosition === self::CHILD_POSITION_LAST)) {
			
			throw new \InvalidArgumentException(sprintf('Unknown value [%s]', $childPosition));
		}

		$this->childPosition = $childPosition;
	}

	/**
	 * @return PageLocalization | null
	 */
	protected function getTargetChild()
	{
		$localization = $this->getPageLocalization();

		return $this->childPosition === self::CHILD_POSITION_FIRST
				? array_shift($localization->getChildren())
				: array_pop($localization->getChildren());
	}

}
