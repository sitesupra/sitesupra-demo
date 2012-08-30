<?php
namespace Supra\Editable;

class SelectVisual extends Select
{
	const EDITOR_TYPE = 'SelectVisual';
	
	/**
	 * @var string
	 */
	protected $iconStyle = '';
	
	/**
	 * @var string
	 */
	protected $style = '';
	
	/**
	 * @var string
	 */
	protected $css = '';

	public function getAdditionalParameters()
	{
		$output = array('values' => $this->values, 'iconStyle' => $this->iconStyle, 'style' => $this->style, 'css' => $this->css);

		return $output;
	}

	/**
	 * Set Select visual box values
	 * @example $values = array(array('id' => 'id','title' => 'value','icon' => 'icon'));
	 * @param array $values 
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

	/**
	 * @return string
	 */
	public function getIconStyle()
	{
		return $this->iconStyle;
	}

	/**
	 * Set icon style
	 * @param string $iconStyle
	 */
	public function setIconStyle($iconStyle)
	{
		$this->iconStyle = $iconStyle;
	}

	/**
	 * @return string
	 */
	public function getStyle()
	{
		return $this->style;
	}

	/**
	 * Set style
	 * @param string $style
	 */
	public function setStyle($style)
	{
		$this->style = $style;
	}

	/**
	 * @return string
	 */
	public function getCss()
	{
		return $this->css;
	}

	/**
	 * Set style
	 * @param string $css
	 */
	public function setCss($css)
	{
		$this->css = $css;
	}
}
