<?php

namespace Supra\Editable;

/**
 * Slideshow
 */
class Slideshow extends EditableAbstraction
{
	
	/**
	 * @var boolean
	 */
	protected $separateSlide = true;
	
	/**
	 * @var array
	 */
	protected $layouts;
	
	/**
	 * @return string
	 */
	public function getEditorType()
	{
		return 'Slideshow';
	}

	/**
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return false;
	}
	
	/**
	 * @param boolean $separateSlide
	 */
	public function setSeparateSlide($separateSlide)
	{
		$this->separateSlide = (bool)$separateSlide;
	}
	
	/**
	 * @param array $layouts
	 */
	public function setValues($layouts)
	{
		//@FIXME: fast solution, not cached, not nice
		foreach ($layouts as &$layout) {
			$fileName = SUPRA_COMPONENT_PATH . $layout['fileName'];
			$layout['html'] = file_get_contents($fileName);									
		}
		
		$this->layouts = $layouts;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'layouts' => $this->layouts,
			'separateSlide' => $this->separateSlide,
		);
	}
	
	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}
	
	public function setContent($content)
	{
		if (is_array($content)) {
			$content = serialize($content);
		}
		
		$this->content = $content;
	}
	
	public function getContentForEdit()
	{
		$unserialized = unserialize($this->content);
		return $unserialized;
	}
}
