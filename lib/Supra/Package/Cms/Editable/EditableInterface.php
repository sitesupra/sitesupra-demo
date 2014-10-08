<?php

namespace Supra\Package\Cms\Editable;

/**
 * Interface for editable content class
 */
interface EditableInterface
{
	/**
	 * Loads content data
	 * @return mixed
	 */
	public function getContent();
	
	/**
	 * @param string $label
	 */
	public function setLabel($label);
	
	/**
	 * @return string
	 */
	public function getLabel();
	
	/**
	 * @param string $groupLabel
	 */
	public function setGroupId($groupId);
	
	/**
	 * @return string
	 */
	public function getGroupId();
	
	/**
	 * @param string $localeId
	 * @return mixed
	 */
	public function getDefaultValue($localeId = null);
	
	/**
	 * @param mixed $value
	 */
	public function setDefaultValue($value);
	
	
	/**
	 * Get JavaScript editor type
	 * @return string
	 */
	public function getEditorType();
	
	/**
	 * Whether editable can be edited inline
	 * @return boolean
	 */
	public function isInlineEditable();
	
	/**
	 * Sets content data
	 * @param mixed $content
	 */
	public function setContent($content);
	
	public function getContentForEdit();
	
	public function setContentFromEdit($content);
	
	public function getContentMetadata();
	
	public function setContentMetadata($contentMetadata);
	
	public function getContentMetadataForEdit();
	
	public function setContentMetadataFromEdit($contentMetadata);
	
	/**
	 * Get filtered value for the editable content by action
	 * @return mixed
	 */
	public function getFilteredValue();
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters();
	
	/**
	 * Get editable description/hint
	 * @return string
	 */
	public function getDescription();
	
	/**
	 * @param string $description
	 */
	public function setDescription($description);
}