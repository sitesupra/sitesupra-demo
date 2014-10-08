<?php

namespace Supra\Package\Cms\Editable;

/**
 * Media Gallery
 */
class MediaGallery extends EditableAbstraction
{
	
	const EDITOR_TYPE = 'MediaGallery';
	
	/**
     * @var \Supra\Controller\Pages\MediaGallery\MediaGalleryLayoutConfiguration[]
     */
    protected $layoutConfigurations;
	
	/**
     * {@inheritdoc}
     */
    public function getEditorType()
    {
        return self::EDITOR_TYPE;
    }
	
	/**
     * {@inheritdoc}
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
		$layouts = array();
		foreach ($this->layoutConfigurations as $layoutConfiguration) {
			$layouts[] = array(
				'id' => $layoutConfiguration->name,
				'title' => $layoutConfiguration->title,
				'html' => $layoutConfiguration->getLayoutFileContent(),
			);
		}
		
        return array(
			'layouts' => $layouts,
        );
    }
	
	/**
	 * @param array $layoutConfigurations
	 */
	public function setLayouts($layoutConfigurations)
	{
		$this->layoutConfigurations = $layoutConfigurations;
	}
	
	public function setSeparateSlide()
	{
		
	}
		
	/**
	 */
	public function setContent($content)
	{
		if ( ! empty($content)) {
			
			$unserializedData = unserialize($content);
			
			if ($unserializedData !== false) {
				$this->content = $unserializedData;
			}
		}
	}
	
	/**
	 */
	public function setContentFromEdit($content)
	{
		$this->content = $content;
	}
	
	/**
	 */
	public function getContentForEdit()
	{
		return $this->content;
	}
	
	/**
	 */
	public function getStorableContent()
	{
		return serialize($this->content);
	}
	
}
