<?php

namespace Supra\Html;

/**
 * Description of HtmlTag
 */
class HtmlTag
{
	/**
	 * @var string
	 */
	protected $tagName;
	
	/**
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * @var string
	 */
	protected $content;
	
	/**
	 * @param string $tagName
	 * @param string $content
	 */
	public function __construct($tagName, $content = null)
	{
		$this->setTagName($tagName);
		$this->setContent($content);
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	private function normalizeName($name)
	{
		return strtolower($name);
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setAttribure($name, $value)
	{
		//TODO: name validation
		$name = $this->normalizeName($name);
		$this->attributes[$name] = $value;
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
			$this->setAttribure('class', $class);
		} else {
			$this->attributes['class'] .= ' ' . $class;
		}
	}
	
	/**
	 * @param string $tagName
	 */
	public function setTagName($tagName)
	{
		$this->tagName = $this->normalizeName($tagName);
	}

	/**
	 * @param string $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function toHtml()
	{
		$html = '<' . $this->tagName;
		
		foreach ($this->attributes as $name => $value) {
			$html .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
		}
		
		if ( ! is_null($this->content)) {
			$html .= '>' . $this->content;
			$html .= '</' . $this->tagName . '>';
		} else {
			$html .= '/>';
		}
		
		return $html;
	}
	
}
