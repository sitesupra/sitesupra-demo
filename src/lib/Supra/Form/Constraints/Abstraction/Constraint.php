<?php

namespace Supra\Form\Constraints\Abstraction;

abstract class Constraint extends \Symfony\Component\Validator\Constraint
{

	public $propertyMessages = array(
	);

	public function validatedBy()
	{
		$className = get_class($this) . 'Validator';

		if ( ! @class_exists($className)) {
			$className = '\\Symfony\\Component\\Validator\\Constraints\\' . array_pop(explode('\\', get_class($this))) . 'Validator';
		}

		return $className;
	}

	public function __construct($options = null)
	{
		$vars = get_class_vars(get_class($this));

		foreach ($vars as $key => $value) {
			if (stripos($key, 'message') !== false) {
				if ($key == 'propertyMessages') {
					continue;
				}
				
				$className = strtolower(array_pop(explode('\\', get_class($this))));
				$propertyName = "constraint_{$className}_{$key}";
				
				$this->propertyMessages[$key] = $value;
				
				$this->$key = $propertyName;
			}
		}

		parent::__construct($options);
	}

}