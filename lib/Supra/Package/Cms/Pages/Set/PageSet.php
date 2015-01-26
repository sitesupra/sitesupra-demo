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

use Supra\Package\Cms\Entity\Abstraction\AbstractPage;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\Theme\ThemeLayout;

class PageSet extends AbstractSet
{
	/**
	 * Gets root template (first element in the set).
	 * 
	 * @return Template
	 */
	public function getRootTemplate()
	{
		return $this->getFirstElement();
	}
	
	/**
	 * Gets final page (last element in the set).
	 *
	 * @return AbstractPage
	 */
	public function getFinalPage()
	{
		return $this->getLastElement();
	}
	
	/**
	 * Get layout for the page hierarchy (last one in the stack)
	 * @param string $media
	 * @return ThemeLayout
	 */
	public function getLayout($media)
	{
		throw new \Exception('Do not use me.');

		$layout = null;
		$trace = array();
		
		foreach ($this as $abstractPage) {
			if ($abstractPage instanceof Template) {
				if ($abstractPage->hasLayout($media)) {
					$layout = $abstractPage->getLayout($media);
				}
			}

			$trace[] = (string) $abstractPage;
		}
		
		return $layout;
	}

	/**
	 * Get theme layout for the page hierarchy (last one in the stack).
	 * 
	 * @param string $media
	 * @return string
	 */
	public function getLayoutName($media)
	{
		$layout = null;
		$trace = array();

		foreach ($this as $abstractPage) {
			if ($abstractPage instanceof Template) {
				if ($abstractPage->hasLayout($media)) {
					$layout = $abstractPage->getLayoutName($media);
				}
			}

			$trace[] = (string) $abstractPage;
		}

		return $layout;
	}
}
