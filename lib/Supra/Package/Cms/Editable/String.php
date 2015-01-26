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

/**
 * String editable content
 */
class String extends Editable
{
	const EDITOR_TYPE = 'String';
	
	protected $maxLength;

	/**
	 * If editable is read only.
	 * @var boolean
	 */
	protected $disabled = false;

	/**
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;
	}

	/**
	 * @return boolean
	 */
	public function getDisabled()
	{
		return $this->disabled;
	}

	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}

	/**
	 * Which fields to serialize
	 * @return array
	 */
	public function __sleep()
	{
		$fields = parent::__sleep() + array('disabled');

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return static::EDITOR_INLINE_EDITABLE;
	}
	
	/*
	 * @return integer
	 */
	public function getMaxLength()
	{
		return $this->maxLength;
	}

	/*
	 * @param integer $maxLength
	 */
	public function setMaxLength($maxLength)
	{
		$this->maxLength = $maxLength;
	}
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{				
		return array(
			'disabled' => $this->getDisabled(),
			'maxLength' => $this->getMaxLength(),
		);
	}

}
