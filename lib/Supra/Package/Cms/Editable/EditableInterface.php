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

namespace Supra\Package\Cms\Editable;

use Supra\Package\Cms\Editable\Filter\FilterInterface;

/**
 * Interface for editable content class
 */
interface EditableInterface
{
	/**
	 * @return string
	 */
	public function getEditorType();

	/**
	 * @return string
	 */
	public function getLabel();

//	/**
//	 * @param string $label
//	 */
//	public function setLabel($label);

	/**
	 * @return string
	 */
	public function getDescription();

//	/**
//	 * @param string $description
//	 */
//	public function setDescription($description);

	/**
	 * @return array
	 */
	public function getAdditionalParameters();

	/**
	 * @param string $localeId
	 * @return mixed
	 */
	public function getDefaultValue($localeId = null);

//	/**
//	 * @param mixed $value
//	 */
//	public function setDefaultValue($value);
//
//	/**
//	 * @return string
//	 */
//	public function getGroupId();
//
//	/**
//	 * @param string $groupLabel
//	 */
//	public function setGroupId($groupId);

//	/**
//	 * @return mixed
//	 */
//	public function getContent();
//
//	/**
//	 * @param mixed $content
//	 */
//	public function setContent($content);

	/**
	 * @param mixed $value
	 * @param array $options
	 * @return mixed
	 */
	public function toViewValue($value, array $options = array());

	/**
	 * @param FilterInterface $filter
	 */
	public function addViewFilter(FilterInterface $filter);
}