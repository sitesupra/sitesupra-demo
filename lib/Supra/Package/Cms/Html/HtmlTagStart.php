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

namespace Supra\Package\Cms\Html;

class HtmlTagStart extends HtmlTagAbstraction
{

	/**
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setAttribute($name, $value, $forceNoNormalization = false)
	{
		//TODO: name validation
		if ( ! $forceNoNormalization) {
			$name = $this->normalizeName($name);
		}
		$this->attributes[$name] = $value;
		
		return $this;
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
		return $this;
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function getAttribute($name)
	{
		return $this->attributes[$name];
	}

	/**
	 * @param string $class
	 */
	public function addClass($class)
	{
		if (empty($class)) {
			return;
		}

		if ( ! isset($this->attributes['class'])) {
			$this->setAttribute('class', $class);
		}
		else {
			$this->attributes['class'] .= ' ' . $class;
		}
		
		return $this;
	}

	/**
	 * Returns beginning part of tag - without any closing ">" or "/>".
	 * @return string 
	 */
	private function getHtmlBeginning()
	{
		$html = '<' . $this->tagName;

		foreach ($this->attributes as $name => $value) {
			$html .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
		}

		return $html;
	}

	/**
	 * Returns opened tag.
	 * @return string
	 */
	protected function getHtmlForOpenTag()
	{
		return $this->getHtmlBeginning() . '>';
	}

	/**
	 * Returns closed tag.
	 * @return string
	 */
	protected function getHtmlForClosedTag()
	{
		return $this->getHtmlBeginning() . '/>';
	}

	/**
	 * @return string
	 */
	public function toHtml()
	{
		return $this->getHtmlForOpenTag();
	}

}

