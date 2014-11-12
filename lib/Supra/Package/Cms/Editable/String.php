<?php

namespace Supra\Package\Cms\Editable;

/**
 * String editable content
 */
class String extends Editable
{
	const EDITOR_TYPE = 'String';
	
	protected $maxLength;

	/**
	 * If editable is read only.
	 * @var boolean
	 */
	protected $disabled = false;

	/**
	 * @param string $label
	 * @param string $groupId
	 * @param array $options
	 */
	public function __construct($label = null, $groupId = null, $options = array())
	{
		if (isset($options['disabled'])) {
			$this->disabled = (boolean) $options['disabled'];
		}
	}

	/**
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;
	}

	/**
	 * @return boolean
	 */
	public function getDisabled()
	{
		return $this->disabled;
	}

	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}

	/**
	 * Which fields to serialize
	 * @return array
	 */
	public function __sleep()
	{
		$fields = parent::__sleep() + array('disabled');

		return $fields;
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
			'disabled' => $this->getDisabled(),
			'maxLength' => $this->getMaxLength(),
		);
	}

}
