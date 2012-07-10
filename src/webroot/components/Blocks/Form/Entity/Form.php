<?php

namespace Project\Blocks\Form\Entity;

use Symfony\Component\Validator\Constraints;
use Supra\Form\FormField;

class Form
{

	/**
	 * @FormField(type="text")
	 * 
	 * @Constraints\NotBlank
	 * @Constraints\MinLength(3)
	 * @Constraints\Email(checkMX="true")
	 */
	public $name;

	/**
	 * @FormField(type="text")
	 * 
	 * @Constraints\Email(checkMX="true")
	 */
	public $lastName;

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

}
