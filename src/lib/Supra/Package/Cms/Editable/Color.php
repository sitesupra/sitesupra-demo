<?php

namespace Supra\Package\Cms\Editable;

/**
 * Select editable content
 */
class Color extends EditableAbstraction
{
	const EDITOR_TYPE = 'Color';
	const EDITOR_INLINE_EDITABLE = false;

	/**
	 * @var boolean
	 */
	protected $allowUnset = false;

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
		$output = array('allowUnset' => $this->allowUnset);

		return $output;
	}

	/**
	 * @return boolean 
	 */
	public function getAllowUnset()
	{
		return $this->allowUnset;
	}

	/**
	 * Set Color unset value
	 * @param boolean $allowUnset
	 */
	public function setAllowUnset($allowUnset)
	{
		$this->allowUnset = $allowUnset;
	}
}
