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
	 * Sets content data
	 * @param mixed $content
	 */
	public function setContent($content);
	
	/**
	 * Get filtered value for the editable content by action
	 * @return string
	 */
	public function getFilteredValue();
}
