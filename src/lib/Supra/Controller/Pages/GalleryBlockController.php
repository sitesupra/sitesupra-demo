<?php

namespace Supra\Controller\Pages;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Editable\EditableInterface;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Dummy controller to fetch subproperties of specified metadata
 */
class GalleryBlockController extends BlockController
{
	
	/**
	 * @var BlockPropertyMetadata
	 */
	protected $metadata;
	
	/**
	 * @var BlockPropertyConfiguration
	 */
	protected $propertyConfiguration;


	public function getProperty($name)
	{
		$parentProperty = $this->metadata->getBlockProperty();
		$parentName = $parentProperty->getName();

		$parentDefinition = $this->configuration->getProperty($parentName);
		foreach($parentDefinition->properties as $property) {
			if ($property->name === $name) {
				$propertyDefinition = $property;
				break;
			}
		}

		if ( ! isset($propertyDefinition)) {
			throw new Exception\RuntimeException("Content '{$name}' is not defined for block ");
		}

		$editable = $propertyDefinition->editableInstance;
		if ( ! $editable instanceof EditableInterface) {
			throw new Exception\RuntimeException("Definition of property must be an instance of editable");
		}

		// Find property by name
		$property = null;
		$expectedType = get_class($editable);
		
		$existentProperties = $this->getMetadataProperties();
		
		foreach ($existentProperties as $propertyCheck) {
			/* @var $propertyCheck BlockProperty */
			/* @var $property BlockProperty */
			if ($propertyCheck->getName() === $name) {

				if ($propertyCheck->getType() === $expectedType) {
					$property = $propertyCheck;
					break;
				}
			}
		}

		/*
		 * Must create new property here
		 */
		$propertySet = $this->getRequest()
				->getBlockPropertySet();
		
		$localization = $this->getRequest()
				->getPageLocalization();
		
		if (empty($property)) {
			$property = new Entity\BlockProperty($name);
			$property->setEditable($editable);
			$property->setValue($editable->getDefaultValue());
			$property->setBlock($this->getMetadataBlock());
			$property->setLocalization($localization);
			
			$property->setMasterMetadata($this->metadata);
			
			$propertySet->append($property);
		}

		$editable = $property->getEditable();

		//TODO: do this some way better..
		$this->configureContentFilters($property, $editable);
		
		return $property;
	}
	
	public function setParentMetadata($metadata)
	{
		$this->metadata = $metadata;
		$this->prepareController();
	}
	
	/**
	 * @param type $request
	 */
	public function setRequest($request)
	{
		$this->request = $request;
	}
	
	/**
	 * @return BlockPropertyConfiguration
	 */
	public function getConfiguration()
	{
		return $this->propertyConfiguration;
	}
	
	/**
	 *
	 */
	protected function getMetadataBlock()
	{
		$propertySet = $this->getRequest()
				->getBlockPropertySet();
		
		$propertyId = $this->metadata->getBlockProperty()
				->getId();
		
		$property = null;
		foreach ($propertySet as $blockProperty) {
			if ($blockProperty->getId() === $propertyId) {
				$property = $blockProperty;
				break;
			}
		}
		
		if (is_null($property)) {
			throw new Exception\RuntimeException('No property found in property set!');
		}
		
		if ($property instanceof Entity\SharedBlockProperty) {
			$property = $property->getReplacedBlockProperty();
		}
		
		$block = $property->getBlock();
	
		return $block;
	}
	
	/**
	 * 
	 */
	protected function prepareController()
	{
		// get block, to load block configuration
		$block = $this->getMetadataBlock();
		
		// get property name, to load configuration on it
		$propertyName = $this->metadata->getBlockProperty()
				->getName();
		
		$controllerCollection = BlockControllerCollection::getInstance();
		$controller = $controllerCollection->createBlockController($block->getComponentClass());

		// block configuration
		$this->configuration = $controller->getConfiguration();
		
		// property configuration
		$this->propertyConfiguration = $this->configuration->getProperty($propertyName);
	}
	
	/**
	 *
	 * @return BlockPropertySet
	 */
	public function getMetadataProperties()
	{
		$propertySet = $this->getRequest()
				->getBlockPropertySet(true);
		
		$subPropertySet = $propertySet->getMetadataProperties($this->metadata);

		return $subPropertySet;
	}
	

}