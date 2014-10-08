<?php

namespace Supra\Package\Cms\Editable;

use Supra\Controller\Pages\Entity\ReferencedElement;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\File;
use Supra\FileStorage\Entity\Image;

/**
 * Abstract class for editable content classes
 */
abstract class EditableAbstraction implements EditableInterface
{
	/**
	 * Array of content filters 
	 * @var array
	 */
	protected $filters = array();

	/**
	 * @return mixed
	 */
	protected $content;
	
	/**
	 * @var array
	 */
	protected $contentMetadata = array();

	/**
	 * Content label
	 * @var string
	 */
	protected $label;

	/**
	 * Grouping id
	 * @var string
	 */
	protected $group;
	
	/**
	 * Default value
	 * @var mixed
	 */
	protected $defaultValue;
	
	/**
	 * Description for the editable
	 * @var string
	 */
	protected $description;

	/**
	 * Default values (localized)
	 * @var array
	 */
	protected $defaultValueLocalized = array();

	/**
	 * @param string $label
	 */
	public function __construct($label = null, $groupId = null, $options = array())
	{
		$this->setLabel($label);
		$this->setGroupId($groupId);
	}

	/**
	 * Loads content data
	 * @return mixed
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Sets content data
	 * @param mixed $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

	public function getContentForEdit()
	{
		return $this->content;
	}

	public function setContentFromEdit($content)
	{
		$this->content = $content;
	}
	
	/**
	 * @return type
	 */
	public function getContentMetadata()
	{
		return $this->contentMetadata;
	}

	/**
	 * @param type $contentMetadata
	 */
	public function setContentMetadata($contentMetadata)
	{
		$this->contentMetadata = $contentMetadata;
	}

	/**
	 * 
	 * @return type
	 */
	public function getContentMetadataForEdit()
	{
		return $this->contentMetadata;
	}

	/**
	 * @param type $contentMetadata
	 */
	public function setContentMetadataFromEdit($contentMetadata)
	{
		$this->contentMetadata = $contentMetadata;
	}
	
	/**
	 * Adds filter for the editable content, one of type
	 * @param Filter\FilterInterface $filter
	 */
	public function addFilter(Filter\FilterInterface $filter)
	{
		$this->filters[get_class($filter)] = $filter;
	}

	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * Get filtered value for the editable content by action
	 * @param string $action
	 * @return string
	 */
	public function getFilteredValue()
	{
		$content = $this->content;

		// Filter the content
		foreach ($this->filters as $filter) {
			$content = $filter->filter($content);
		}

		return $content;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}

	/**
	 * @TODO: Remove later. Kept only for backward compatibility. Use getGroupId instead.
	 * @return string
	 */
	public function getGroupLabel()
	{
		return $this->getGroupId();
	}

	/**
	 * @TODO: Remove later. Kept only for backward compatibility. Use setGroupId instead.
	 * @param string $groupLabel
	 */
	public function setGroupLabel($groupLabel)
	{
		$this->setGroupId($groupLabel);
	}

		/**
	 * @return string BlockPropertyGroupConfiguration id
	 */
	public function getGroupId()
	{
		return $this->group;
	}

	/**
	 * @param string $groupId BlockPropertyGroupConfiguration id
	 */
	public function setGroupId($groupId)
	{
		$this->group = $groupId;
	}
	/**
	 * @param string $localeId
	 * @return mixed 
	 */
	public function getDefaultValue($localeId = null)
	{
		if ( ! is_null($localeId) && array_key_exists($localeId, $this->defaultValueLocalized)) {
			return $this->defaultValueLocalized[$localeId];
		}

		return $this->defaultValue;
	}

	/**
	 * @param mixed $value
	 * @param string $localeId
	 */
	public function setDefaultValue($value, $localeId = null)
	{
		if ( ! is_null($localeId)) {
			$this->defaultValueLocalized[$localeId] = $value;

			if ( ! is_null($this->defaultValue)) {
				return;
			}
		}

		$this->defaultValue = $value;
	}

	/**
	 * Which fields to serialize
	 * @return array
	 */
	public function __sleep()
	{
		$fields = array(
			'label',
			'defaultValue'
		);

		return $fields;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array();
	}
	
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}
	
	public static function CN()
	{
		return get_called_class();
	}
	
	
	/**
	 * @return mixed
	 */
	public function getStorableContent()
	{
		return $this->getContent();
	}
	
	
	/**
	 * Converts referenced element to JS array
	 * @param ReferencedElement\ReferencedElementAbstract $element
	 * @return array
	 */
	protected function convertReferencedElementToArray(ReferencedElement\ReferencedElementAbstract $element)
	{
		$fileData = array();

		$storage = ObjectRepository::getFileStorage($this);
		
		if ($element instanceof ReferencedElement\LinkReferencedElement) {
			
			if ($element->getResource() == ReferencedElement\LinkReferencedElement::RESOURCE_FILE) {

				$fileId = $element->getFileId();

				if ( ! empty($fileId)) {
					
					$file = $storage->find($fileId, File::CN());

					if ( ! is_null($file)) {
						$fileInfo = $storage->getFileInfo($file);
						$fileData['file_path'] = $fileInfo['path'];
					}
				}
			}
		}
		
		else if ($element instanceof ReferencedElement\ImageReferencedElement) {

			$imageId = $element->getImageId();

			if ( ! empty($imageId)) {
				$image = $storage->find($imageId, Image::CN());

				if ( !is_null($image)) {
					$info = $storage->getFileInfo($image);
					$fileData['image'] = $info;
				}
			}
		}
		
		else if ($element instanceof ReferencedElement\IconReferencedElement) {
			
			$iconId = $element->getIconId();
			
			$themeConfiguration = ObjectRepository::getThemeProvider($this)
					->getCurrentTheme()
					->getConfiguration();
			
			$iconConfiguration = $themeConfiguration->getIconConfiguration();
			if ($iconConfiguration instanceof \Supra\Controller\Layout\Theme\Configuration\ThemeIconSetConfiguration) {
				$fileData['svg'] = $iconConfiguration->getIconSvgContent($iconId);
			}
	
		}
		
		$data = $fileData + $element->toArray();

		return $data;
	}

	/**
	 * @param string $name
	 * @return EditableAbstraction
	 * @throws \InvalidArgumentException
	 */
	public static function get($name)
	{
		switch ($name) {
			case 'html':
				return new Html;
			default:
				throw new \InvalidArgumentException("Unrecognized editable [{$name}].");
		}
	}
}
