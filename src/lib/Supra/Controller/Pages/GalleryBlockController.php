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
		
		$existentPropertyCollection = $this->metadata->getMetadataProperties();

		foreach ($existentPropertyCollection as $propertyCheck) {
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
		if (empty($property)) {
			$property = new Entity\BlockProperty($name);
			$property->setEditable($editable);
			$property->setValue($editable->getDefaultValue());
			$property->setBlock($parentProperty->getBlock());
			$property->setLocalization($parentProperty->getLocalization());
			
			$property->setMasterMetadata($this->metadata);
			
			$existentPropertyCollection->add($property);
		}

		$editable = $property->getEditable();

		//TODO: do this some way better..
		$this->configureContentFilters($property, $editable);
		
		return $property;
	
	}
	
	public function setParentMetadata($metadata)
	{
		$this->metadata = $metadata;
		
		// self preparing
		$this->page = $this->metadata->getBlockProperty()
				->getLocalization()
				->getMaster();
		
		$block = $this->metadata->getBlockProperty()
				->getBlock();
		
		$controllerCollection = BlockControllerCollection::getInstance();
		
		// original gallery controller
		$controller = $controllerCollection->getBlockController($block->getComponentClass());
		$this->configuration = $controller->getConfiguration();
		$this->request = $controller->getRequest();
		
	}
	
	protected function configureContentFilters(Entity\BlockProperty $property, EditableInterface $editable)
	{
		$propertyId = $property->getId();

		if (array_key_exists($propertyId, $this->configuredBlockProperties)) {
			return;
		}

		// Html content additional filters
		if ($editable instanceof Editable\Html) {
			// Editable action
			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
				$filter = new Filter\EditableHtml();
				$filter->property = $property;
				$editable->addFilter($filter);
				// View
			} else {
				$filter = new Filter\ParsedHtmlFilter();
				ObjectRepository::setCallerParent($filter, $this);
				$filter->property = $property;
				$editable->addFilter($filter);
			}
		}

		if ($editable instanceof Editable\Link) {
			$filter = new Filter\LinkFilter();
			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		if ($editable instanceof Editable\InlineString) {
			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
				$filter = new Filter\EditableString();
				ObjectRepository::setCallerParent($filter, $this);
				$filter->property = $property;
				$editable->addFilter($filter);
			}
		}

		if ($editable instanceof Editable\Textarea) {
			$filter = new Filter\EditableTextarea();
			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		if ($editable instanceof Editable\Gallery) {
			$filter = new Filter\GalleryFilter();
			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		$this->configuredBlockProperties[$propertyId] = true;
	}
	
	public function setRequest($request)
	{
		$this->request = $request;
	}
}