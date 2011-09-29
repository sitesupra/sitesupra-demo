<?php

namespace Supra\Configuration\Parser;

use Symfony\Component\Yaml\Yaml;
use Supra\ObjectRepository\ObjectRepository;

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
		if (\class_exists($className)) {

			$object = new $className();
			foreach ($properties as $propertyName => $propertyValue) {
				$possibleSetterName = 'set' . ucfirst($propertyName);
//				$log = ObjectRepository::getLogger($this);
//				$log::write(array($possibleSetterName));
				if (\property_exists($className, $propertyName)) {
					$object->$propertyName = 
							$this->processItem($propertyValue);
				} else if (\method_exists($object, $possibleSetterName)) {
					$reflection = new \ReflectionClass($object);
					$methodParams = $reflection->getMethod($propertyName)->getParameters();
					if (count($methodParams) == 1) {
						$propertyValue = $this->processItem($propertyValue);
						$object->$propertyName($propertyValue);
					}
				}
			}
			if (\method_exists($object, 'configure')) {
				$object->configure();
			}
			return $object;
			
		}
	}

	/**
	 * Process item
	 *
	 * @param mixed $item
	 * @return mixed
	 */
	protected function processItem($item) {
		$return = $item;
		if (\is_array($item) && (\count($item) == 1)) {
			$value = \end($item);
			$key = \key($item);
			if (($key == 'const') && \defined($value)) {
				$return = \constant($value);
			} else {
				// try to setup config object
				$object = $this->processObject($key, $value);
				if (\is_object($object)) {
					$return = $object;
				}
			}
		} if (\is_array($item)) {
			foreach ($item as &$subitem) {
				$subitem = $this->processItem($subitem);
			}
		}
		return $return;
	}
	
}
