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
}
