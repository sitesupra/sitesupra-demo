<?php

namespace Supra\Package\Cms\Editable;

/**
 * Select editable content
 */
class Select extends Editable
{
	const EDITOR_TYPE = 'Select';

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

		if (! empty($this->options['values'])) {

			foreach ($this->options['values'] as $label => $value) {
				$values[] = array('id' => $label, 'title' => $value);
			}

		}

		return array('values' => $values);
	}
}
