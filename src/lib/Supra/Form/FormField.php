<?php

namespace Supra\Form;

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
	 * Field type, can be null
	 * @var string 
	 */
	protected $type;
	
	/**
	 * Field name
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $groups = array();

	/**
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Stores error message information – constraint and original message
	 * @var array
	 */
	protected $errorInfo;
	
	/**
	 * @param array $arguments
	 */
	public function __construct(array $arguments = array())
	{
		if (isset($arguments['type'])) {
			$this->type = $arguments['type'];
			unset($arguments['type']);
		}
		if (isset($arguments['groups'])) {
			$this->groups = $arguments['groups'];
			unset($arguments['groups']);
		}
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
	 * @param array $errorInfo
	 */
	public function setErrorInfo(array $errorInfo)
	{
		$this->errorInfo = $errorInfo;
	}

	/**
	 * @return array
	 */
	public function getErrorInfo()
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

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setArgument($name, $value)
	{
		$this->arguments[$name] = $value;
	}

	/**
	 * @param array $groups
	 * @return boolean
	 */
	public function inGroups($groups)
	{
		if (empty($this->groups)) {
			return true;
		}

		if (empty($groups)) {
			return true;
		}

		// Skip field if groups have no common values
		if (array_intersect($this->groups, $groups) == array()) {
			return false;
		}

		return true;
	}

}
