<?php

namespace Supra\Html;

class HtmlTagEnd extends HtmlTagAbstraction
{

	/**
	 * @return string
	 */
	public function toHtml()
	{
		return '</' . $this->tagName . '>';
	}

}

