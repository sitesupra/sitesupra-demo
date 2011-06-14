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
	public function getData();
	
	/**
	 * Sets content data
	 * @param mixed $data
	 */
	public function setData($data);
}
