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

namespace Supra\Package\Cms\Pages\Markup\Abstraction;

use Supra\Package\Cms\Pages\Markup\Exception;

abstract class SupraMarkupElement extends ElementAbstraction
{

	/**
	 * @var string
	 */
	protected $signature;

	/**
	 * @return string
	 */
	public function getSignature()
	{
		return $this->signature;
	}

	/**
	 * @param string $signature 
	 */
	public function setSignature($signature)
	{
		$this->signature = $signature;
	}

	public function setSource($source)
	{
		$this->source = $source;
	}

	public function getSource($source)
	{
		return $this->source;
	}

	protected function extractValueFromSource($key)
	{
		$key = preg_quote($key);

		$match = array();

		preg_match('@' . $key . '="(?<value>.*?)"@ims', $this->source, $match);

		if (empty($match['value'])) {
			throw new Exception\RuntimeException('Could not extract value for key "' . $key . '" from source.');
		}

		return $match['value'];
	}

	public function parseSource()
	{
		return true;
	}

}
