<?php

namespace Supra\Editable;

/**
 * Select editable content
 */
class Select extends EditableAbstraction
{
	const EDITOR_TYPE = 'Select';
	const EDITOR_INLINE_EDITABLE = false;

	/**
	 * @var array
	 */
	protected $values = array();

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

	public function getAdditionalParameters()
	{
		$values = array();

		foreach ($this->values as $label => $value) {
			$values[] = array('id' => $label, 'title' => $value);
		}

		$output = array('values' => $values);

		return $output;
	}

	public function getValues()
	{
		return $this->values;
	}

	public function setValues($values)
	{
		$this->values = $values;
	}
}
