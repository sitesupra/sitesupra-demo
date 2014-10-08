<?php

namespace Supra\Package\Cms\Editable;

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
     * @var string
     */
    protected $icon;
    
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
			'icon' => $this->icon,
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
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }
    
    /**
     * @param boolean $separateSlide
     */
    public function setSeparateSlide($separateSlide)
    {
        $this->separateSlide = (bool)$separateSlide;
    }
	
	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}
	
	/**
	 * This value is coming from BlockProperty
	 * @param mixed $content
	 */
	public function setContent($content)
	{
		$value = null;
		
		if ( ! empty($content)) {
			$value = unserialize($content);
			if ($value === false) {
				$value = null;
			}
		}
		
		$this->content = $value;
	}
	
	/**
	 * Return serialized value to store as BlockPropertyValue
	 * @return string
	 */
	public function getStorableContent() 
	{
		if ( ! empty($this->content)) {
			return serialize($this->content);
		}
				
		return null;
	}
	
	/**
	 * Expecting something like array
	 * @param mixed $content
	 */
	public function setContentFromEdit($content)
	{
		if (empty($content)) {
			$this->content = null;
		}
		
		$this->content = $content;
	}
	
	/**
	 * Return as is
	 * @return array
	 */
	public function getContentForEdit()
	{
		return $this->content;
	}
}
