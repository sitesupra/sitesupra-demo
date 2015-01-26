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
 * Number editable type
 */
class Number extends String
{
	const EDITOR_TYPE = 'Number';
	
	private $minValue;
	private $maxValue;
	private $step = 1;
	private $allowReals = false;
	private $showButtons = true;
	
	public function getMinValue()
	{
		return $this->minValue;
	}

	public function setMinValue($minValue)
	{
		$this->minValue = $minValue;
	}

	public function getMaxValue()
	{
		return $this->maxValue;
	}

	public function setMaxValue($maxValue)
	{
		$this->maxValue = $maxValue;
	}
	
	public function getStep()
	{
		return $this->step;
	}

	public function setStep($step)
	{
		$this->step = $step;
	}

	public function setAllowReals($allowReals)
	{
		$this->allowReals = (bool) $allowReals;
	}

	public function setShowButtons($showButtons)
	{
		$this->showButtons = (bool) $showButtons;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'minValue' => $this->minValue,
			'maxValue' => $this->maxValue,
			'step' => $this->step,
			'allowRealNumbers' => $this->allowReals,
			'showButtons' => $this->showButtons,
		);
	}
}
