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
	 * @Constraints\Length(min=3, groups={"developer"})
	 */
	public $name;

	/**
	 * @FormField(type="text", required=false)
	 *
	 * @Constraints\Length(min=3)
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
	 * @FormField(type="email", required=false)
	 *
	 * @Constraints\NotBlank(groups={"developer"}, message="Developers must enter secondary email")
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

//	/**
//	 * @FormField(type="submit")
//	 */
//	public $submit;
	
	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

}
