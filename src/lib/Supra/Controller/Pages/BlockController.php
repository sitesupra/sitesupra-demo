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
	 * @var array
	 */
	protected $properties = array();

	/**
	 * Block which created the controller
	 * @var Entity\Abstraction\Block
	 */
	protected $block;
	
	/**
	 * Loads property definition array
	 * TODO: should be fetched automatically from simple configuration file (e.g. YAML)
	 * @return array
	 */
	abstract protected function getPropertyDefinition();

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = null;
		
		// TODO: create edit response only for not locked blocks
		if ($request instanceof namespace\Request\RequestEdit) {
			$response = new BlockResponse\ResponseEdit();
		} else {
			$response = new BlockResponse\ResponseView();
		}
		
		return $response;
	}

	/**
	 * @param array $properties
	 */
//	public function addProperties(array $properties)
//	{
//		$this->properties = array_merge($this->properties, $properties);
//	}

	/**
	 * @param array $properties
	 */
//	public function setProperties(array $properties)
//	{
//		$this->properties = $properties;
//	}

	/**
	 * @param Entity\BlockProperty $property
	 */
	public function addProperty(Entity\BlockProperty $property)
	{
		$name = $property->getName();
		
		$this->properties[$name] = $property;
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
			throw new Exception("The property '{$name}' was not found for block '{$blockName}'");
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
		return \array_key_exists($name, $this->properties);
	}
	
	/**
	 * Get the content and output it to the response or return if no response 
	 * object set
	 * 
	 * @TODO move to block response object
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
				throw new Exception("Definition of property must be an instance of editable");
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
			 * 
			 * FIXME: there is no real nead to create new property here because 
			 * we will be using only content here...
			 */
			if ($newProperty) {
				
				$propertyType = get_class($editable);
				
				$property = new Entity\BlockProperty($name, $propertyType);
				$property->setValue($default);
				$property->setBlock($this->getBlock());
				
				// Must set some DATA object. Where to get this? And why data is set to property not block?
				//$property->setData();
			}
			
			$content = $property->getValue();
			$editable->setContent($content);
		} else {
			throw new Exception("Content '{$name}' is not defined for block ");
		}
		
		$response = $this->getResponse();
		
		if ( ! $response instanceof BlockResponse\Response) {
			throw new Exception("Block controller response object must be instance of block response");
		}
		
		$response->outputEditable($editable);
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
		return $this->block;
	}
	
}