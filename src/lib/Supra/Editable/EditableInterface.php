<?php

namespace Supra\Editable;

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
	public function setGroupLabel($groupLabel);
	
	/**
	 * @return string
	 */
	public function getGroupLabel();
	
	/**
	 * @return mixed
	 */
	public function getDefaultValue();
	
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
	
	/**
	 * Get filtered value for the editable content by action
	 * @return mixed
	 */
	public function getFilteredValue();
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters();
}