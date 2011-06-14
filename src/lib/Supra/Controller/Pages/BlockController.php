<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Request,
		Supra\Response,
		Supra\Editable\EditableAbstraction,
		Supra\Editable\EditableInterface;

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
		return new Response\Http();
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
	 * @param string $name
	 * @param string $default
	 * @param Response\ResponseInterface $response
	 * @return string
	 */
	public function outputProperty($name, $default = null, Response\ResponseInterface $response = null)
	{
		$data = $this->getPropertyValue($name, $default);
		
		$propertyDefinitions = $this->getPropertyDefinition();
		$editable = null;
		
		if (isset($propertyDefinitions[$name])) {
			$editable = $propertyDefinitions[$name];
			
			if ( ! $editable instanceof EditableInterface) {
				throw new Exception("Definition of property must be an instance of editable");
			}
		}
		
		if ( ! empty($editable)) {
			$editable->setData($data);
		}
		
		// Default action is VIEW
		$action = EditableAbstraction::ACTION_VIEW;
		
		// Check the editing mode by the request class
		$request = $this->getRequest();
		if ($request instanceof Request\HttpEditRequest) {
			$action = EditableAbstraction::ACTION_EDIT;
		}
		
		$filteredValue = $editable->getFilteredValue($action);
		
		if ($response instanceof Response\ResponseInterface) {
			$response->output($filteredValue);
			return;
		}
		
		return $filteredValue;
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