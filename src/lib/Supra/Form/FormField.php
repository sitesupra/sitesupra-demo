<?php

namespace Supra\Form;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 */
class FormField
{

	const TYPE_TEXT = 'text';
	const TYPE_TEXTAREA = 'textarea';
	const TYPE_PASSWORD = 'password';
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_FILE = 'file';
	const TYPE_RADIO = 'radio';
	const TYPE_HIDDEN = 'hidden';
	// select box
	const TYPE_CHOICE = 'choice';
	
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
		self::TYPE_TEXT,
		self::TYPE_TEXTAREA,
		self::TYPE_PASSWORD,
		self::TYPE_CHECKBOX,
		self::TYPE_FILE,
		self::TYPE_RADIO,
		self::TYPE_HIDDEN,
		self::TYPE_CHOICE,
	);
	
	/**
	 * @var array
	 */
	protected $constraints = array();

	/**
	 * @var array
	 */
	protected $arguments = array();
	
	protected $errorInfo;
	
	/*
	 * 
	 */
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
		$this->arguments  = $arguments;
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
//		// validation
//		foreach ($constraints as $constraint) {
//			if ( ! $constraint instanceof Constraint) {
//				throw new Exception\RuntimeException('Passed constraint is not instance of Supra\Form\Constraints\Abstraction\Constraint. ', $constraint);
//			}
//		}
		
		$this->constraints = $constraints;
	}

	public function setErrorInfo($errorInfo)
	{
		$this->errorInfo = $errorInfo;
	}

	public function getErrorinfo()
	{
		return $this->errorInfo;
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

	/**
	 * Get field arguments array
	 * 
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}
	
	/**
	 * Get field argument
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getArgument($name)
	{
		if (isset($this->arguments[$name])) {
			return $this->arguments[$name];
		}
		
		return null;
	}


}