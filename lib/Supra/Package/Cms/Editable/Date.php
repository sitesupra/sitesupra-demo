<?php

namespace Supra\Package\Cms\Editable;

use \DateTime;

/**
 * Date editable content
 */
class Date extends EditableAbstraction
{

	const EDITOR_TYPE = 'Date';
	const EDITOR_INLINE_EDITABLE = false;

	/**
	 * If editable is read only.
	 * @var boolean
	 */
	protected $disabled = false;

	/**
	 * @var DateTime
	 */
	protected $minDate;

	/**
	 * @var DateTime
	 */
	protected $maxDate;

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

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'disabled' => $this->getDisabled(),
			'minDate' => $this->getMinDate(),
			'maxDate' => $this->getMaxDate(),
		);
	}

	/**
	 * @return DateTime
	 */
	public function getMinDate()
	{
		return $this->minDate;
	}

	/**
	 * @param DateTime $minDate
	 */
	public function setMinDate(DateTime $minDate)
	{
		$this->minDate = $minDate;
	}

	/**
	 * @return DateTime
	 */
	public function getMaxDate()
	{
		return $this->maxDate;
	}

	/**
	 * @param DateTime $minDate
	 */
	public function setMaxDate(DateTime $maxDate)
	{
		$this->maxDate = $maxDate;
	}

}
