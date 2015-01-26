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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;

/**
 * Page controller template class
 * @Entity(repositoryClass="Supra\Package\Cms\Repository\TemplateRepository")
 * @method TemplateLocalization getLocalization(string $locale)
 */

class Template extends Abstraction\AbstractPage
{
	/**
	 * {@inheritdoc}
	 */

	const DISCRIMINATOR = self::TEMPLATE_DISCR;

	/**
	 * @OneToMany(
	 *		targetEntity="TemplateLayout",
	 *		mappedBy="template",
	 *		cascade={"persist", "remove"},
	 *		indexBy="media"
	 * )
	 * 
	 * @var Collection
	 */
	protected $templateLayouts;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->templateLayouts = new ArrayCollection();
	}

	/**
	 * Set templateLayout
	 * @param TemplateLayout $templateLayout
	 */
	public function addTemplateLayout(TemplateLayout $templateLayout)
	{
		if ($this->lock('templateLayouts')) {

			$media = $templateLayout->getMedia();

			$this->templateLayouts->set($media, $templateLayout);
			$templateLayout->setTemplate($this);

			$this->unlock('templateLayouts');
		}
	}

	/**
	 * Get template templateLayout
	 * @return Collection
	 */
	public function getTemplateLayouts()
	{
		return $this->templateLayouts;
	}

	/**
	 * Add layout for specific media.
	 * 
	 * @param string $media
	 * @param ThemeLayoutInterface $layout
	 * @return TemplateLayout
	 */
	public function addLayout($media, ThemeLayoutInterface $layout)
	{
		$templateLayout = new TemplateLayout($media);
		$templateLayout->setLayoutName($layout->getName());
		$templateLayout->setTemplate($this);

		return $templateLayout;
	}

	/**
	 * Whether the layout exists
	 * @param string $media
	 * @return boolean
	 */
	public function hasLayout($media = TemplateLayout::MEDIA_SCREEN)
	{
		$has = $this->templateLayouts->offsetExists($media);

		return $has;
	}

	/**
	 * Get layout name for specified media type.
	 *
	 * @param string $media
	 * @return string
	 */
	public function getLayoutName($media = TemplateLayout::MEDIA_SCREEN)
	{
		$templateLayouts = $this->getTemplateLayouts();

		if ($templateLayouts->offsetExists($media)) {
			$templateLayout = $templateLayouts->offsetGet($media);
			/* @var $templateLayout TemplateLayout */

			return $templateLayout->getLayoutName();
		}

		throw new \RuntimeException(sprintf(
				'Template [%s] has no layout for media [%s]',
				$this->getId(),
				$media
		));
	}

	
	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		return __CLASS__;
	}

}
