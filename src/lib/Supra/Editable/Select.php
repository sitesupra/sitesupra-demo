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
	protected $options;

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

		$this->options = $content; // Is this a hack?

		if ( ! is_null($defaultId)) {
			$this->setDefaultValue($defaultId);
		}
	}

	public function getAdditionalParameters()
	{
		$values = array();

		foreach ($this->options as $label => $value) {
			$values[] = array('id' => $label, 'title' => $value);
		}

		return array('values' => $values);
	}

	public function getContent()
	{
		return parent::getContent();
	}

}
