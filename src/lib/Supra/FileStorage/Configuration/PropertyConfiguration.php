<?php

namespace Supra\FileStorage\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Editable\EditableAbstraction;
use Supra\Loader\Loader;

class PropertyConfiguration implements ConfigurationInterface
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
	 * @var string
	 */
	public $default;
	
	public function configure()
	{		
		$fileStorage = ObjectRepository::getFileStorage($this);
		$fileStorage->addCustomPropertyConfiguration($this);
	}
	
	public function getEditable()
	{
		return Loader::getClassInstance($this->editable, EditableAbstraction::CN());
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
		);
	}
}