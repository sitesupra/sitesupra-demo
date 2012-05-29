<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\Editable\EditableInterface;

/**
 * Block Property Configuration
 */
class BlockPropertyConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $editable;
	
	/**
	 * Generated editable instance
	 * @var EditableInterface
	 */
	public $editableInstance;

	/**
	 * @var string
	 */
	public $label;
	
	/**
	 * @var string
	 */
	public $default;
	
	/**
	 * @var string
	 */
	public $group;

	/**
	 * @var boolean
	 */
	public $shared;
	
	/**
	 * Hash table for editable additional properties
	 * @var array
	 */
	public $additionalParameters = array();
	
	public function configure()
	{
		$this->editableInstance = Loader::getClassInstance($this->editable, 'Supra\Editable\EditableInterface');
		$this->editableInstance->setLabel($this->label);
		$this->editableInstance->setDefaultValue($this->default);

		//FIXME: not nice. Editable might inform about its additionals maybe?
		foreach ($this->additionalParameters as $name => $value) {

			$methodName = 'set' . $name;

			if (method_exists($this->editableInstance, $methodName)) {
				$this->editableInstance->$methodName($value);
			} else {
				\Log::warn("No additional parameter setter found for editable {$this->editable} with name {$name}");
			}
		}
	}
	
}
