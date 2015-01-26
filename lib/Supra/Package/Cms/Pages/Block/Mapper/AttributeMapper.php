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

class AttributeMapper extends Mapper
{
	public function title($title)
	{
		$this->config->setTitle($title);
		return $this;
	}

	public function description($description)
	{
		$this->config->setDescription($description);
		return $this;
	}

	public function icon($icon)
	{
		$this->config->setIcon($icon);
		return $this;
	}

	public function tooltip($tooltip)
	{
		$this->config->setTooltip($tooltip);
		return $this;
	}

	public function group($group)
	{
		$this->config->setGroupName($group);
		return $this;
	}

	public function insertable($insertable = true)
	{
		$this->config->setInsertable($insertable);
		return $this;
	}

	/**
	 * Alias for insertable()
	 * 
	 * @param bool $hidden
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper
	 */
	public function hidden($hidden = true)
	{
		return $this->insertable(! $hidden);
	}

	/**
	 * @param string $templateName
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper
	 */
	public function template($templateName)
	{
		$this->config->setTemplateName($templateName);
		return $this;
	}

	/**
	 * @param string $controllerClass
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper
	 */
	public function controller($controllerClass)
	{
		$this->config->setControllerClass($controllerClass);
		return $this;
	}

	/**
	 * @TODO: refactor this.
	 *
	 * @param string $cmsClassName
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper
	 */
	public function cmsClassName($cmsClassName)
	{
		$this->config->setCmsClassName($cmsClassName);
		return $this;
	}
}
