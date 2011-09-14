<?php

namespace Supra\Editable;

/**
 * String editable content
 */
class String extends EditableAbstraction
{
	const EDITOR_TYPE = 'String';
	const EDITOR_INLINE_EDITABLE = true;
	
	private $defaultValue = '';
	
	/**
	 * Default filter classes for content by action
	 * @var array
	 */
	protected static $defaultFilters = array(
		'Supra\Editable\Filter\EscapeHtml',
	);
	
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return self::EDITOR_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return self::EDITOR_INLINE_EDITABLE;
	}
	
	/**
	 * @return mixed 
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param mixed $value 
	 */
	public function setDefaultValue($value)
	{
		$this->defaultValue = $value;
	}


}
