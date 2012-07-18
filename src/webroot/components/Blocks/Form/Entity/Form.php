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
	 */
	public $name;

	/**
	 * @FormField(type="text", required=false)
	 *
	 * @Constraints\MinLength(3)
	 */
	public $nickname;

	/**
	 * @FormField(type="email")
	 *
	 * @Constraints\NotBlank
	 * @Constraints\Email
	 */
	public $email;

	/**
	 * @FormField(type="text", required=false)
	 * 
	 * @Constraints\Email
	 */
	public $secondaryEmail;

	/**
	 * @FormField(type="password")
	 */
	public $password;

	/**
	 * @FormField(type="password")
	 */
	public $repeatPassword;

	/**
	 * @FormField(type="textarea", required=false)
	 */
	public $additionalNotes;
	

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

}
