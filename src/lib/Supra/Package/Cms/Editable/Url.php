<?php

namespace Supra\Package\Cms\Editable;

/**
 * Auto-filled URL
 * Based on the same JS components as "Page URL" input for pages
 * Has "valueMask" option used for the UI validation of inputed value
 * and "valueSource" - to specify id of input, which will be used as content source
 */
class Url extends EditableAbstraction
{
	const EDITOR_TYPE = 'Path';
	const EDITOR_INLINE_EDITABLE = false;

	/**
	 * @var string
	 */
	protected $valueMask;
	
	/**
	 * @var string
	 */
	protected $valueSource;
	
	
	/**
	 * {@inheritdoc}
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
	 * @param string $valueMask
	 */
	public function setValueMask($valueMask)
	{
		$this->valueMask = $valueMask;
	}
	
	/**
	 * @param string $valueSource
	 */
	public function setValueSource($valueSource)
	{
		$this->valueSource = $valueSource;
	}
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'valueMask' => $this->valueMask,
			'valueSource' => $this->valueSource,
		);
	}
}
