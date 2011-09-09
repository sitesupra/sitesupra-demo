<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Request;
use Supra\Response;
use Supra\Editable\EditableAbstraction;
use Supra\Editable\EditableInterface;
use Supra\Controller\Pages\Request\HttpEditRequest;
use Supra\Controller\Pages\Response\Block;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Request\PageRequestEdit;

/**
 * Block controller abstraction\
 * @method PageRequest getRequest()
 */
abstract class BlockController extends ControllerAbstraction
{
	/**
	 * @var Set\BlockPropertySet
	 */
	protected $properties = array();

	/**
	 * Block which created the controller
	 * @var Entity\Abstraction\Block
	 */
	protected $block;
	
	/**
	 * Current request page
	 * @var Entity\Abstraction\Page
	 */
	protected $page;
	
	/**
	 * Loads property definition array
	 * TODO: should be fetched automatically from simple configuration file (e.g. YAML)
	 * @return array
	 */
	abstract protected function getPropertyDefinition();

	/**
	 * @return Entity\Abstraction\Page
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @param Entity\Abstraction\Page $page
	 */
	public function setPage(Entity\Abstraction\Page $page)
	{
		$this->page = $page;
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\TwigResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new Response\TwigResponse($this);
		
		return $response;
	}

	/**
	 * @param Set\BlockPropertySet $blockPropertySet
	 */
	public function setBlockPropertySet(Set\BlockPropertySet $blockPropertySet)
	{
		$this->properties = $blockPropertySet;
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return Entity\BlockProperty
	 */
	public function getProperty($name, $default)
	{
		$property = null;
		
		if ($this->propertyExists($name)) {
			$property = $this->properties[$name];
		}
		
		$propertyDefinitions = $this->getPropertyDefinition();
		$editable = null;
		
		if ( ! isset($propertyDefinitions[$name])) {
			throw new Exception\RuntimeException("Content '{$name}' is not defined for block ");
		}
		
		$editable = $propertyDefinitions[$name];

		if ( ! $editable instanceof EditableInterface) {
			throw new Exception\RuntimeException("Definition of property must be an instance of editable");
		}

		$newProperty = false;

		if (empty($property)) {
			$newProperty = true;
		} else {
			$propertyType = (string) $property->getType();

			if ( ! $editable instanceof $propertyType) {
				$newProperty = true;
			}
		}

		/*
		 * Must create new property here
		 */
		if ($newProperty) {

			$propertyType = get_class($editable);

			$property = new Entity\BlockProperty($name, $propertyType);
			$property->setValue($default);
			$property->setBlock($this->getBlock());

			// Must set some DATA object. Where to get this? And why data is set to property not block?
			//FIXME: should do somehow easier than that
			$property->setData($this->getRequest()->getRequestPageData());
		}

		//TODO: this is ugly content copying
		$content = $property->getValue();
		$editable->setContent($content);
		$property->setEditable($editable);

		$this->configureContentFilters($property, $editable);
		
		return $property;
	}
	
	/**
	 * Add additional filters for the property
	 * @param Entity\BlockProperty $property
	 * @param EditableInterface $editable
	 */
	protected function configureContentFilters(Entity\BlockProperty $property, EditableInterface $editable)
	{
		// Html content additional filters
		if ($editable instanceof \Supra\Editable\Html) {
			// Editable action
			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
				$filter = new Filter\EditableHtml();
				$filter->property = $property;
				$editable->addFilter($filter);
			// View
			} else {
				$filter = new Filter\ParsedHtmlFilter();
				$filter->property = $property;
				$editable->addFilter($filter);
			}
		}
	}
	
	/**
	 * Get property value, use default if not found
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function getPropertyValue($name, $default = null)
	{
		$property = $this->getProperty($name, $default);
		$editable = $property->getEditable();
		$value = $editable->getFilteredValue();
		
		return $value;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function propertyExists($name)
	{
		return array_key_exists($name, $this->properties);
	}
	
	/**
	 * Get the content and output it to the response or return if no response 
	 * object set
	 * 
	 * @TODO could move to block response object
	 * 
	 * @param string $name
	 * @param string $default
	 */
	public function outputProperty($name, $default = null)
	{
		$value = $this->getPropertyValue($name, $default);
		
		$this->response->output($value);
	}

	/**
	 * Set the block which called the controller
	 * @param Entity\Abstraction\Block $block
	 */
	public function setBlock(Entity\Abstraction\Block $block)
	{
		$this->block = $block;
	}

	/**
	 * Get the block which created the controller
	 * @return Entity\Abstraction\Block
	 */
	public function getBlock()
	{
		// Workaround for not existent block
		//TODO: remove
		if (empty($this->block)) {
			$this->block = new Entity\PageBlock();
		}
		
		return $this->block;
	}
	
}