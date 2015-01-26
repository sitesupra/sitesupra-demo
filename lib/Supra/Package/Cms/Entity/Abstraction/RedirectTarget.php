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

namespace Supra\Package\Cms\Entity\Abstraction;

use Supra\Package\Cms\Entity\PageLocalization;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"child" = "Supra\Package\Cms\Entity\RedirectTargetChild",
 *		"page"	= "Supra\Package\Cms\Entity\RedirectTargetPage",
 *		"url"	= "Supra\Package\Cms\Entity\RedirectTargetUrl"
 * })
 */
abstract class RedirectTarget extends Entity
{
	/**
	 * @OneToOne(targetEntity="Supra\Package\Cms\Entity\PageLocalization")
	 * @var PageLocalization
	 */
	protected $pageLocalization;

	/**
	 * @return string
	 */
	abstract public function getRedirectUrl();

	/**
	 * @param PageLocalization $localization
	 */
	public function setPageLocalization(PageLocalization $localization)
	{
		$this->pageLocalization = $localization;
	}

	/**
	 * @return PageLocalization
	 */
	protected function getPageLocalization()
	{
		return $this->pageLocalization;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersionedParent()
	{
		return $this->pageLocalization;
	}
	
}
