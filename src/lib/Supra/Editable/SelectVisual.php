<?php
namespace Supra\Editable;

class SelectVisual extends Select
{
	const EDITOR_TYPE = 'SelectVisual';

	public function getAdditionalParameters()
	{
		$output = array('values' => $this->values);

		return $output;
	}

	/**
	 * Set Select visual box values
	 * @example $values = array(array('id' => 'id','title' => 'value','icon' => 'icon'));
	 * @param array $values 
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}
}
