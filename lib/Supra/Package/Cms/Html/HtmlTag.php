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

/**
 * Html tag object
 */
class HtmlTag extends HtmlTagStart
{

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var HtmlTagEnd
	 */
	private $endTag;
	protected $forceTwoPartTag = false;

	/**
	 * @param string $tagName
	 * @param string $content
	 */
	public function __construct($tagName, $content = null)
	{
		parent::__construct($tagName);

		$this->endTag = new HtmlTagEnd($tagName);

		$this->setContent($content);
	}

	public function forceTwoPartTag($forceTwoPartTag)
	{
		$this->forceTwoPartTag = $forceTwoPartTag;
	}

	/**
	 * @param string $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @return string
	 */
	public function toHtml()
	{
		$html = null;

		if ( ! is_null($this->content) || $this->forceTwoPartTag) {
			$html = $this->getHtmlForOpenTag() . $this->content . $this->endTag->toHtml();
		} else {
			$html = $this->getHtmlForClosedTag();
		}

		return $html;
	}

}
