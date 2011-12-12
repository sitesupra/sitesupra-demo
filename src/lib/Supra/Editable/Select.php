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

	/**
	 * @param string $label
	 * @param array $content 
	 * @param string $defaultId 
	 * @example new Select('Cities', array(array('id' => 0, 'title' => 'Riga')), 0);
	 */
	public function __construct($label, $content = array(), $defaultId = null)
	{
		$this->setLabel($label);
		$this->setContent($content);
		
		if ( ! is_null($defaultId)) {
			$this->setDefaultValue($defaultId);
		}
	}

}
