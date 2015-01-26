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
 * Date editable content
 */
class DateTime extends Editable
{
	const EDITOR_TYPE = 'Date';

	/**
	 * If editable is read only.
	 * @var boolean
	 */
	protected $disabled = false;

	/**
	 * @var \DateTime
	 */
	protected $minDate;

	/**
	 * @var \DateTime
	 */
	protected $maxDate;

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
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'disabled' => $this->getDisabled(),
			'minDate' => $this->getMinDate(),
			'maxDate' => $this->getMaxDate(),
		);
	}

	/**
	 * @return \DateTime
	 */
	public function getMinDate()
	{
		return $this->minDate;
	}

	/**
	 * @param \DateTime $minDate
	 */
	public function setMinDate(\DateTime $minDate)
	{
		$this->minDate = $minDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getMaxDate()
	{
		return $this->maxDate;
	}

	/**
	 * @param \DateTime $minDate
	 */
	public function setMaxDate(\DateTime $maxDate)
	{
		$this->maxDate = $maxDate;
	}

}
