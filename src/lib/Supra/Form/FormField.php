<?php

namespace Supra\Form;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FormField extends Constraint
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
	
	const TYPE_REPEATED = 'repeated';
	
	/**
	 * Field type, can be null
	 * @var string 
	 */
	protected $type;
	
	/**
	 * @var array
	 */
	protected $fieldGroups = array(Constraint::DEFAULT_GROUP);

	/**
	 * @var array
	 */
	protected $fieldOptions = array();

	/**
	 * @param array $fieldOptions
	 */
	public function __construct(array $fieldOptions = array())
	{
		if (isset($fieldOptions['type'])) {
			$this->type = $fieldOptions['type'];
			unset($fieldOptions['type']);
		}

		if (isset($fieldOptions['groups'])) {
			$this->fieldGroups = $fieldOptions['groups'];
			unset($fieldOptions['groups']);
		}
		
		// Will not check this constraint now
		$this->groups = array();
		
		$this->fieldOptions = $fieldOptions;
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
	 * Get field option array
	 * 
	 * @return array
	 */
	public function getFieldOptions()
	{
		return $this->fieldOptions;
	}
	
	/**
	 * Get field argument
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getFieldOption($name)
	{
		if (isset($this->fieldOptions[$name])) {
			return $this->fieldOptions[$name];
		}
		
		return null;
	}

	/**
	 * @param array $groups
	 * @return boolean
	 */
	public function inGroups($groups)
	{
		if (empty($this->fieldGroups)) {
			return true;
		}

		if (empty($groups)) {
			return true;
		}

		// Skip field if groups have no common values
		if (array_intersect($this->fieldGroups, $groups) == array()) {
			return false;
		}

		return true;
	}

}
