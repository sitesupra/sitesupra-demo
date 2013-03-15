<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction;

abstract class ThemeParameterConfigurationAbstraction extends ThemeConfigurationAbstraction
{

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $label;

	/**
	 * @var boolean
	 */
	public $noLess = false;

	/**
	 * @var mixed
	 */
	public $values = null;

	/**
	 * @var ThemeParameterAbstraction
	 */
	protected $parameter;
	
	/**
	 * @return string
	 */
	abstract protected function getParameterClass();
	
	/**
	 * @return string
	 */
	abstract public function getEditorType();

	/**
	 * @return ThemeParameterAbstraction
	 */
	public function getParameter()
	{
		return $this->parameter;
	}

	/**
	 * 
	 */
	public function readConfiguration()
	{
		$theme = $this->getTheme();

		$parameters = $theme->getParameters();

		$parameter = null;
		$parameterClass = $this->getParameterClass();

		if (
				empty($parameters[$this->id]) ||
				get_class($parameter) != $parameterClass
		) {

			$parameter = new $parameterClass();

			$parameter->setName($this->id);
		} else {
			$parameter = $parameters[$this->id];
		}

		$parameter->setTitle($this->label);

		$this->parameter = $parameter;
	}
	
	/**
	 * @return array
	 */
	public function toArray()
	{
		return array (
			'type' => $this->getEditorType(),
			'id' => $this->id,
			'label' => $this->label,
			'noLess' => $this->noLess,
			'values' => $this->values,
		);
	}
	
	/**
	 * @return array
	 */
	public function getAdditionalProperties()
	{
		return array();
	}

}
