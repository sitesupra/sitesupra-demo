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

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Html\HtmlTag;

/**
 * Filters the value to enable Html editing for CMS
 */
class EditableHtmlFilter extends HtmlFilter
{
	/**
	 * Filters the editable content's data, adds Html Div node for CMS.
	 *
	 * @param string $content
	 * @param array $options
	 * @return string
	 */
	public function filter($content, array $options = array())
	{
		$wrap = '<div id="content_%s_%s" class="su-content-inline su-input-html-inline-content">%s</div>';
		
		return sprintf(
					$wrap,
					$this->blockProperty->getBlock()->getId(),
					str_replace('.', '_', $this->blockProperty->getHierarchicalName()),
					parent::filter($content)
		);
	}

	/**
	 * {@inheritDoc}
	 * Additionally, adds data-* attributes required for CMS editor.
	 */
	protected function parseSupraImage(ImageReferencedElement $imageElement)
	{
		$tag = parent::parseSupraImage($imageElement);

		if ($tag === null) {
			return null;
		}

		if (! $tag instanceof HtmlTag) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting HtmlTagAbstraction, [%s] received.',
					get_class($tag)
			));
		}

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		$image = $fileStorage->findImage($imageElement->getImageId());

		if ($image !== null) {
			
			$exists = $fileStorage->fileExists($image);
			$tag->setAttribute('data-exists', $exists);

			if (! $exists) {
				$tag->setAttribute('width', null);
				$tag->setAttribute('height', null);
				$tag->setAttribute('src', null);
			}
		}

		return $tag;
	}
}
