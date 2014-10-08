<?php

namespace Supra\Package\Cms\Editable;

use Supra\Controller\Pages\Entity\ReferencedElement;

/**
 * Image editable
 */
class InlineMedia extends EditableAbstraction
{
	const EDITOR_TYPE = 'InlineMedia';
	const EDITOR_INLINE_EDITABLE = true;
	
	/**
     * @var boolean
     */
    protected $allowVideo = true;
    
    /**
     * @var boolean
     */
    protected $allowImage = true;
    
    /**
     * @var boolean
     */
    protected $fixedMaxCropWidth = true;
	
	/**
     * @var boolean
     */
    protected $fixedMaxCropHeight = false;
    
    /**
     * @var boolean
     */
    protected $autoClose = false;
    
    /**
     * @var boolean
     */
    protected $allowCropZooming = false;
    
	/**
     *
     */
    public function getAdditionalParameters()
    {
        return array(
            'allowVideo' => $this->allowVideo,
            'allowImage' => $this->allowImage,
            'fixedMaxCropWidth' => $this->fixedMaxCropWidth,
			'fixedMaxCropHeight' => $this->fixedMaxCropHeight,
            'autoClose' => $this->autoClose,
            'allowCropZooming' => $this->allowCropZooming,
        );
    }
    
    /**
     * @param boolean $allowVideo
     */
    public function setAllowVideo($allowVideo)
    {
        $this->allowVideo = (bool)$allowVideo;
    }
    
    /**
     * @param boolean $allowImage
     */
    public function setAllowImage($allowImage)
    {
        $this->allowImage = (bool)$allowImage;
    }
    
    /**
     * @param boolean $allowImage
     */
    public function setFixedMaxCropWidth($fixedMaxCropWidth)
    {
        $this->fixedMaxCropWidth = (bool)$fixedMaxCropWidth;
    }
	
	 /**
     * @param boolean $allowImage
     */
    public function setFixedMaxCropHeight($fixedMaxCropHeight)
    {
        $this->fixedMaxCropHeight = (bool)$fixedMaxCropHeight;
    }
    
    /**
     * @param boolean $allowImage
     */
    public function setAutoClose($autoClose)
    {
        $this->autoClose = (bool)$autoClose;
    }
    
    /**
     * @param boolean $allowCropZooming
     */
    public function setAllowCropZooming($allowCropZooming)
    {
        $this->allowCropZooming = (bool)$allowCropZooming;
    }
    
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return static::EDITOR_INLINE_EDITABLE;
	}
	
	/**
	 * 
	 * @param type $content
	 */
	public function setContent($content)
	{
		if (is_array($content) && isset($content['type'])) {
			$this->contentMetadata = ReferencedElement\ReferencedElementAbstract::fromArray($content);
		}
	}
	
	/**
	 * @param mixed $content
	 */
	public function setContentFromEdit($content)
	{
		$mediaElement = null;
		
		if ( ! empty($content)) {
			
			$type = isset($content['type']) ? $content['type'] : null;
			
			switch ($type) {
				case ReferencedElement\ImageReferencedElement::TYPE_ID:
					$mediaElement = new ReferencedElement\ImageReferencedElement;
					$mediaElement->fillArray($content);
					break;

				case ReferencedElement\VideoReferencedElement::TYPE_ID:
					switch ($content['resource']) {
						case ReferencedElement\VideoReferencedElement::RESOURCE_SOURCE:

							if ( ! empty($content['source'])) {

								$mediaElement = new ReferencedElement\VideoReferencedElement;
								$videoData = $mediaElement::parseVideoSourceInput($content['source']);

								if ($videoData === false) {
									throw new Exception\RuntimeException("Video link/source you provided is invalid or this video service is not supported. Sorry about that.");
								}

								$content = $videoData + $content;
							}

							break;

						case ReferencedElement\VideoReferencedElement::RESOURCE_LINK:
						case ReferencedElement\VideoReferencedElement::RESOURCE_FILE:
							$mediaElement = new ReferencedElement\VideoReferencedElement;
					}

					if ( ! is_null($mediaElement)) {
						$mediaElement->fillArray($content);
					}

					break;

				default: 
					throw new Exception\RuntimeException("Unknown media type {$type} received");
			}
		}
		
		$this->contentMetadata = $mediaElement;
	}
	
	/**
	 * @return array|null
	 */
	public function getContentForEdit()
	{
		if ($this->contentMetadata instanceof ReferencedElement\ReferencedElementAbstract) {
			
			$data = $this->contentMetadata->toArray();

			if ($this->contentMetadata instanceof ReferencedElement\ImageReferencedElement) {

				$imageId = $this->contentMetadata->getImageId();

				$storage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
				$image = $storage->find($imageId, \Supra\FileStorage\Entity\Image::CN());

				if (is_null($image)) {
					\Log::warn("Failed to find image #{$imageId} for referenced element");
					return $data;
				}

				$data['image'] = $storage->getFileInfo($image);
			}
			
			return $data;
		}
		
		return $this->contentMetadata;
	}
}
