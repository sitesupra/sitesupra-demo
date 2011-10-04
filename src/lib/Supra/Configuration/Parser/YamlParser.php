<?php

namespace Supra\Configuration\Parser;

use Symfony\Component\Yaml\Yaml;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader;

/**
 * YAML configuration parser
 *
 */
class YamlParser extends AbstractParser
{
	
	/**
	 * Parse YAML config
	 *
	 * @param string $filename 
	 */
	public function parse($input) 
	{
		$data = Yaml::parse($input);

		foreach ($data as $item) {
			$this->processItem($item);
		}
	}

	/**
	 * Process item value as config object definition
	 *
	 * @param string $className
	 * @param array $properties
	 * @return object
	 */
	protected function processObject($className, $properties)
	{
		try {
			
			/* @var $object \Supra\Configuration\ConfigurationInterface */
			$object = Loader\Loader::getClassInstance($className, 'Supra\Configuration\ConfigurationInterface');
			
			foreach ($properties as $propertyName => $propertyValue) {
				// For now ignoring the setter function matching
//				$possibleSetterName = 'set' . ucfirst($propertyName);
				
				if (property_exists($className, $propertyName)) {
					$object->$propertyName = 
							$this->processItem($propertyValue);
//				} else if (method_exists($object, $possibleSetterName)) {
//					$reflection = new \ReflectionClass($object);
//					$methodParams = $reflection->getMethod($possibleSetterName)->getParameters();
//					
//					if (count($methodParams) == 1) {
//						$propertyValue = $this->processItem($propertyValue);
//						$object->$propertyName($propertyValue);
//					}
				} else {
					$this->log->warn("Property $propertyName doesn't exist for configuration object $className");
				}
			}
			
			$object->configure();
			
			return $object;
			
		} catch (Loader\Exception\LoaderException $e) {
			$this->logWarn($e->getMessage());
		}
	}

	/**
	 * Process item
	 *
	 * @param mixed $item
	 * @return mixed
	 */
	protected function processItem($item)
	{
		$return = $item;

		if (is_array($item) && (count($item) == 1)) {
			$value = end($item);
			$key = key($item);
			
			if (($key == 'const') && defined($value)) {
				$return = constant($value);
			} else if (is_string($key)) {
				// try to setup config object
				$object = $this->processObject($key, $value);
				if (is_object($object)) {
					$return = $object;
				}
			}
		} 
		
		if (is_array($return)) {
			foreach ($return as &$subitem) {
				$subitem = $this->processItem($subitem);
			}
		}
		
		return $return;
	}
	
}
