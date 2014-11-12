<?php

namespace Supra\Package\Cms\Editable;

/**
 * Select editable content
 */
class Select extends Editable
{
	const EDITOR_TYPE = 'Select';

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @var boolean
	 */
	protected $disabled;

	/**
	 * @return boolean
	 */
	public function getDisabled()
	{
		return $this->disabled;
	}

	/**
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;
	}

	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}

	public function getAdditionalParameters()
	{
		$values = array();

		foreach ($this->values as $label => $value) {
			$values[] = array('id' => $label, 'title' => $value);
		}

		$output = array('values' => $values, 'disabled' => $this->getDisabled());

		return $output;
	}

	/**
	 * @return array 
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * Set Select box values
	 * @example $values = array('key' => 'value'); Result 'id' => 'key', 'title' => 'value'
	 * @param array $values 
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

}
