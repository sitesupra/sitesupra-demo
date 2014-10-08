<?php

namespace Supra\Package\Cms\Editable;

/**
 * Slideshow
 */
class Slideshow extends EditableAbstraction
{
	/**
	 * @var array
	 */
	protected $layouts;
	
	/**
	 * @var array
	 */
	protected $backgroundMasks = array();
	
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
	 * @param array $masks
	 */
	public function setBackgroundMasks($masks)
	{
		foreach ($masks as $mask) {
			$this->backgroundMasks[$mask['id']] = $mask['fileName'];
		}
	}

	/**
	 * @return array
	 */
	public function getBackgroundMasks()
	{
		return $this->backgroundMasks;
	}
	
	/**
	 * @param array $layouts
	 */
	public function setLayouts($layouts)
	{
		//@FIXME: fast solution, not cached, not nice
		foreach ($layouts as &$layout) {
			$layout['html'] = file_get_contents($layout['fileName']);									
		}
		
		$this->layouts = $layouts;
	}
	
	/**
	 * @return array
	 */
	public function getLayouts()
	{
		return $this->layouts;
	}
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'layouts' => $this->layouts,
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
