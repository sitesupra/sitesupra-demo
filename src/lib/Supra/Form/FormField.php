<?php

namespace Supra\Form;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 */
class FormField
{

	/**
	 * One of $types
	 * @var string 
	 */
	protected $type;
	
	/**
	 * Field name
	 * @var string 
	 */
	protected $name;

	/**
	 * Allowed field types
	 * @var array 
	 */
	private static $types = array(
		'text',
		'textarea',
		'password',
		'checkbox',
		'file',
		'radio',
		'hidden',
	);
	
	/**
	 * @var array
	 */
	protected $constraints = array();

	public function __construct(array $arguments = array())
	{
		if ( ! is_string($arguments['type']) || ! in_array($arguments['type'], self::$types, true)) {
			throw new Exception\RuntimeException(
					"Form type \"{$arguments['type']}\" is not allowed. " .
					"Use one of \"" . join('", "', self::$types) . '" annotation types. '
					. 'Example @FormField(type="text")'
			);
		}

		$this->type = $arguments['type'];
	}

	/**
	 * @return string 
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type 
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return array 
	 */
	public function getConstraints()
	{
		return $this->constraints;
	}

	/**
	 * @param Constraint $constraint 
	 */
	public function addConstraint(Constraint $constraint)
	{
		$this->constraints[] = $constraint;
	}

	/**
	 * Adds constraint
	 * 
	 * @throws Exception\RuntimeException if one of constraints is not Constraint instances
	 * @param array $constraints 
	 */
	public function addConstraints(array $constraints)
	{
		// validation
		foreach ($constraints as $constraint) {
			if ( ! $constraint instanceof Constraint) {
				throw new Exception\RuntimeException('Passed consraint is not instance of Supra\Form\Constraints\Abstraction\Constraint. ', $constraint);
			}
		}
		
		$this->constraints = $constraints;
	}
	
	/**
	 * @return string 
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}



}