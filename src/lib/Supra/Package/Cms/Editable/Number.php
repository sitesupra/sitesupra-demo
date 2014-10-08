<?php

namespace Supra\Package\Cms\Editable;

/**
 * Number editable type
 */
class Number extends String
{
	const EDITOR_TYPE = 'Number';
	
	private $minValue;
	private $maxValue;
	private $step = 1;
	private $allowReals = false;
	private $showButtons = true;
	
	public function getMinValue()
	{
		return $this->minValue;
	}

	public function setMinValue($minValue)
	{
		$this->minValue = $minValue;
	}

	public function getMaxValue()
	{
		return $this->maxValue;
	}

	public function setMaxValue($maxValue)
	{
		$this->maxValue = $maxValue;
	}
	
	public function getStep()
	{
		return $this->step;
	}

	public function setStep($step)
	{
		$this->step = $step;
	}

	public function setAllowReals($allowReals)
	{
		$this->allowReals = (bool) $allowReals;
	}

	public function setShowButtons($showButtons)
	{
		$this->showButtons = (bool) $showButtons;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'minValue' => $this->minValue,
			'maxValue' => $this->maxValue,
			'step' => $this->step,
			'allowRealNumbers' => $this->allowReals,
			'showButtons' => $this->showButtons,
		);
	}
}
