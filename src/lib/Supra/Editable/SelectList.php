<?php
namespace Supra\Editable;

class SelectList extends Select
{
	const EDITOR_TYPE = 'SelectList';
	
	/**
	 * @var string
	 */
	protected $iconStyle = '';
	
	/**
	 * @var string
	 */
	protected $style = '';
    
    /**
     * @var boolean
     */
    protected $multiple = false;


	/**
	 *
	 */
	public function getAdditionalParameters()
	{
		return array(
			'values' => $this->values,
			'iconStyle' => $this->iconStyle, 
			'style' => $this->style,
			'multiple' => $this->multiple,
		);
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
	 * Set multiple options selectable
	 * @param boolean $multiple
	 */
	public function setMultiple($multiple)
	{
	    $this->multiple = $multiple;
	}
}
