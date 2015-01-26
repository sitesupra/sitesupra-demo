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

/**
 * TemplateLocalization class
 * @Entity
 * @method TemplateLocalization getParent()
 * @method Template getMaster()
 */
class TemplateLocalization extends Abstraction\Localization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::TEMPLATE_DISCR;
	
	public function __construct($locale)
	{
		parent::__construct($locale);
		
//		$this->placeHolderGroups = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	/**
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->getMaster();
	}

	/**
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->setMaster($template);
	}
	
	/**
	 * @param string $localizationId
	 * @param string $revisionId
	 * @return string
	 */
	public static function getPreviewUrlForLocalizationAndRevision($localizationId, $revisionId)
	{
		return static::getPreviewUrlForTypeAndLocalizationAndRevision('t', $localizationId, $revisionId);
	}

	/**
	 * @param string $localizationId
	 * @param string $revisionId
	 * @return string
	 */
	public static function getPreviewFilenameForLocalizationAndRevision($localizationId, $revisionId)
	{
		return static::getPreviewFilenameForTypeAndLocalizationAndRevision('t', $localizationId, $revisionId);
	}

}