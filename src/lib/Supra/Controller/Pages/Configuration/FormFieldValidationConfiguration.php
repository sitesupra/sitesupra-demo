<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\Editable\EditableInterface;
use Supra\Editable;

class FormFieldValidationConfiguration implements ConfigurationInterface
{

	/**
	 * @var \Symfony\Component\Validator\Constraint 
	 */
	public $constraint;

	/**
	 *
	 * @var array
	 */
	public $options;

	public function configure()
	{
		if ( ! class_exists($this->constraint)) {
			\Log::error("Constraint {$this->constraint} does not exist. ", $this);
			return;
		}
		
		$className = $this->constraint;
		$this->constraint = new $className($this->options);
		
	}

}