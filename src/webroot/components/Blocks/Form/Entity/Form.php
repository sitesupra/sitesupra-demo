<?php

namespace Project\Blocks\Form\Entity;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class Form
{

	/**
	 * @Assert\NotBlank
	 * @Assert\MinLength(3)
	 * @Assert\Email(checkMX="true")
	 */
	public $name;

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}
	
}