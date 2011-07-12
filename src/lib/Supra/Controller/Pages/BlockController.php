<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Request,
		Supra\Response,
		Supra\Editable\EditableAbstraction,
		Supra\Editable\EditableInterface,
		Supra\Controller\Pages\Request\HttpEditRequest,
		Supra\Controller\Pages\Response\Block as BlockResponse;

/**
 * Block controller abstraction
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
	 * Overriden to specify correct return class
	 * @return \Supra\Controller\Pages\Request\Request
	 */
	public function getRequest()
	{
		return parent::getRequest();
	}
	
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
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = null;
		
		if ($request instanceof namespace\Request\RequestEdit) {
			$response = new BlockResponse\ResponseEdit();
		} else {
			$response = new BlockResponse\ResponseView();
		}
		
		// Response object needs a block entity
		$response->setBlock($this->block);
		
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
	public function getProperty($name)
	{
		if ($this->propertyExists($name)) {
			return $this->properties[$name];
		} else {
			$blockName = get_class($this);
			throw new Exception\RuntimeException("The property '{$name}' was not found for block '{$blockName}'");
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
		$value = $default;
		
		if ($this->propertyExists($name)) {
			$property = $this->getProperty($name);
			$value = $property->getValue();
		}
		
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
		$property = null;
		
		if ($this->propertyExists($name)) {
			$property = $this->getProperty($name);
		}
		
		$propertyDefinitions = $this->getPropertyDefinition();
		$editable = null;
		
		//FIXME: some of this functionality should be moved to getPropertyValue
		if (isset($propertyDefinitions[$name])) {
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
			
			$content = $property->getValue();
			$editable->setContent($content);
		} else {
			throw new Exception\RuntimeException("Content '{$name}' is not defined for block ");
		}
		
		$response = $this->getResponse();
		
		if ( ! $response instanceof BlockResponse\Response) {
			throw new Exception\RuntimeException("Block controller response object must be instance of block response");
		}
		
		//TODO: Here must add filter which would add <DIV> for edit action
		//TODO: Someone passes the actual request page here
//		/* @var $page Entity\Abstraction\Page */
//		if ($page->isBlockPropertyEditable($property)) {
//			
//			$
//			
//			$editable->addFilter();
//		}
		
		$property->setEditable($editable);
		
		$response->outputProperty($property);
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