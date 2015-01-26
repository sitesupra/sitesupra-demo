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

class Checkbox extends Editable
{
	private $yesLabel = '{#buttons.yes#}';
	private $noLabel = '{#buttons.no#}';
	
	const EDITOR_TYPE = 'Checkbox';
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return self::EDITOR_TYPE;
	}
	
	public function setYesLabel($yesLabel)
	{
		$this->yesLabel = $yesLabel;
	}

	public function setNoLabel($noLabel)
	{
		$this->noLabel = $noLabel;
	}
	
	/**
	 * @return string 
	 */
	public function getYesLabel()
	{
		return $this->yesLabel;
	}

	/**
	 * @return string 
	 */
	public function getNoLabel()
	{
		return $this->noLabel;
	}

	
	public function getAdditionalParameters()
	{				
		$output = array(
			'labels' => array(
				$this->yesLabel,
				$this->noLabel,
			),
		);

		return $output;
	}
}
