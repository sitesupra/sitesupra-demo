<?php

namespace Supra\FileStorage\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Editable\EditableAbstraction;
use Supra\Loader\Loader;

class FilePropertyConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var string
	 */
	public $label;
		
	/**
	 * @var string
	 */
	public $editable;

	/**
	 * Editable additional parameters
	 * 
	 * @var array
	 */
	public $additionalParameters = array();
	
	/**
	 * @var string
	 */
	public $default;

	/**
	 * @var EditableAbstraction
	 */
	private $editableInstance;

	public function configure()
	{
	}

	/**
	 * @return EditableAbstraction
	 */
	public function getEditable()
	{
		if ($this->editableInstance === null) {

			$editableClass = is_array($this->editable)
					? $this->editable[0]
					: (string) $this->editable;

			$this->editableInstance = Loader::getClassInstance(
					$editableClass,
					EditableAbstraction::CN()
			);

			if (is_array($this->editable)
					&& ! empty($this->editable[1])) {

				foreach ($this->editable[1] as $name => $value) {

					$setterName = 'set' . ucfirst($name);

					if (! method_exists($this->editableInstance, $setterName)) {
						throw new \RuntimeException(
								"Editable [{$editableClass}] is missing for setter for parameter [{$name}]."
						);
					}

					$this->editableInstance->$setterName($value);
				}
			}
		}

		return $this->editableInstance;
	}
			
	/**
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'id' => $this->name,
			'label' => $this->label,
			'type' => $this->getEditable()->getEditorType(),
		) + $this->getEditable()
				->getAdditionalParameters();
	}
}