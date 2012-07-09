<?php

namespace Supra\Form\Configuration;

use Supra\Configuration\ConfigurationInterface;

class FormFieldConfiguration implements ConfigurationInterface
{

	/**
	 * Field name
	 * @var string 
	 */
	public $name;

	/**
	 * One of $types
	 * @var string 
	 */
	public $type;

	/**
	 * Field label
	 * @var string
	 */
	public $label;

	/**
	 * Field value
	 * @var string 
	 */
	public $value;
	
	/**
	 * @var array 
	 */
	public $validation;

	/**
	 * @return FormFieldConfiguration 
	 */
	public function configure()
	{
		return $this;
	}

}