<?php

namespace Supra\Editable;

/**
 * @TODO: it is possible, that this editable is not needed
 */
class PropertySet extends EditableAbstraction
{
	/**
	 * @var string
	 */
	protected $labelAdd;
	
	/**
	 * @var string
	 */
	protected $labelRemove;
	
	/**
	 * @var string
	 */
	protected $labelItem;
    
    /**
     * @var string
     */
    protected $labelButton;
    
    /**
     * @var boolean
     */
    protected $separateSlide = false;
	
	
	/**
	 * @return string
	 */
	public function getEditorType()
	{
		return 'Set';
	}

	/**
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return false;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'labelAdd' => $this->labelAdd,
			'labelRemove' => $this->labelRemove,
			'labelItem' => $this->labelItem,
			'labelButton' => $this->labelButton,
            'separateSlide' => $this->separateSlide,
		);
	}
	
	/**
	 * @param string $labelAdd
	 */
	public function setLabelAdd($labelAdd)
	{
		$this->labelAdd = $labelAdd;
	}
	
	/**
	 * @param string $labelRemove
	 */
	public function setLabelRemove($labelRemove)
	{
		$this->labelRemove = $labelRemove;
	}
	
	/**
	 * @param string $labelItem
	 */
	public function setLabelItem($labelItem)
	{
		$this->labelItem = $labelItem;
	}
    
    /**
     * @param string $labelButton
     */
    public function setLabelButton($labelButton)
    {
        $this->labelButton = $labelButton;
    }
    
    /**
     * @param boolean $separateSlide
     */
    public function setSeparateSlide($separateSlide)
    {
        $this->separateSlide = (bool)$separateSlide;
    }
}
