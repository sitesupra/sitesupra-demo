<?php

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

