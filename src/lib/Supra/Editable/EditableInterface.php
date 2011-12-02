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