<?php

namespace Supra\Package\Cms\Editable;

/**
 * Video editable
 */
class Video extends EditableAbstraction
{
	const EDITOR_TYPE = 'Video';
	const EDITOR_INLINE_EDITABLE = false;

	/**
	 * @var boolean
	 */
	protected $allowSizeControls = true;

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
	 * @return boolean
	 */
	public function getAllowSizeControls()
	{
		return $this->allowSizeControls;
	}

	/**
	 * @param boolean $allowSizeControls
	 */
	public function setAllowSizeControls($allowSizeControls)
	{
		$this->allowSizeControls = (bool) $allowSizeControls;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'allowSizeControls' => $this->getAllowSizeControls(),
		);
	}
}
