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
 * Page controller template-layout class
 * @Entity
 */
class TemplateLayout extends Abstraction\Entity
{
	const DISCRIMINATOR = self::TEMPLATE_DISCR;

	const MEDIA_SCREEN = 'screen';
	const MEDIA_PRINT = 'print';

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $media;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $layoutName;

	/**
	 * @ManyToOne(targetEntity="Template", inversedBy="templateLayouts")
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
	 * @var Template
	 */
	protected $template;

	/**
	 * Constructor
	 * @param string $media
	 */
	public function __construct($media)
	{
		parent::__construct();
		$this->setMedia($media);
	}

	/**
	 * Set media
	 * @param string $media
	 */
	protected function setMedia($media)
	{
		$this->media = $media;
	}

	/**
	 * Get media
	 * @return string
	 */
	public function getMedia()
	{
		return $this->media;
	}

	/**
	 * @return string
	 */
	public function getLayoutName()
	{
		return $this->layoutName;
	}

	/**
	 * @param string $layoutName 
	 */
	public function setLayoutName($layoutName)
	{
		$this->layoutName = $layoutName;
	}

	/**
	 * Set template
	 * @param Template $template
	 */
	public function setTemplate(Template $template = null)
	{
		if ($this->writeOnce($this->template, $template)) {
			$this->template->addTemplateLayout($this);
		}
	}

	/**
	 * Get template
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}
}