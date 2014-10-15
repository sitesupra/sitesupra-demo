<?php

namespace Supra\Package\Cms\Html;

abstract class HtmlTagAbstraction
{
	/**
	 * @var string
	 */
	protected $tagName;
	
	/**
	 * @param string $tagName
	 * @param string $content
	 */
	function __construct($tagName)
	{
		$this->setTagName($tagName);
	}
	
	/**
	 * @param string $tagName
	 */
	public function setTagName($tagName)
	{
		$this->tagName = $this->normalizeName($tagName);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getTagName()
	{
		return $this->tagName;
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	protected function normalizeName($name)
	{
		return strtolower($name);
	}	
	
	abstract function toHtml();
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->toHtml();
	}
}
