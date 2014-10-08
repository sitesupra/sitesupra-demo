<?php

namespace Supra\Package\Cms\Editable;

/**
 * String editable content
 */
class InlineTextarea extends EditableAbstraction
{
	const EDITOR_TYPE = 'InlineText';
	const EDITOR_INLINE_EDITABLE = true;
	
	private $maxLength;
	
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
	
	/*
	 * @return integer
	 */
	public function getMaxLength()
	{
		return $this->maxLength;
	}

	/*
	 * @param integer $maxLength
	 */
	public function setMaxLength($maxLength)
	{
		$this->maxLength = $maxLength;
	}
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{				
		return array(
			'maxLength' => $this->getMaxLength(),
		);
	}
	
}