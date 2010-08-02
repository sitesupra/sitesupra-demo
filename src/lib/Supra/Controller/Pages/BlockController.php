<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Controller\Request,
		Supra\Controller\Response;

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
	 * Output
	 */
	public function output()
	{
		//TODO: use smarty view
	}

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
	public function addProperties(array $properties)
	{
		$this->properties = array_merge($this->properties, $properties);
	}

	/**
	 * @param array $properties
	 */
	public function setProperties(array $properties)
	{
		$this->properties = $properties;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function addProperty($name, $value = null)
	{
		if ($name instanceof Entity\Abstraction\BlockProperty) {
			$property = $name;
			$name = $property->getName();
			$value = $property->getValue();
		}
		$this->properties[$name] = $value;
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getProperty($name, $default = null)
	{
		if ($this->propertyExists($name)) {
			return $this->properties[$name];
		} else {
			return $default;
		}
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

}