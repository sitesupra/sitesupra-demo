<?php

namespace Supra\Configuration\Loader;

use Supra\Configuration\Parser\ParserInterface;
use Supra\Configuration\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Loader;
use Supra\Configuration\ConfigurationInterfaceParserCallback;
use Supra\Configuration\ConfigurationInterface;

/**
 * Component configuration loader
 */
class ComponentConfigurationLoader
{
	/**
	 * @var WriterAbstraction
	 */
	protected $log;
	
	/**
	 * Filename currently processed
	 * @var string
	 */
	private $configurationFile;
	
	/**
	 * @var ParserInterface
	 */
	private $parser;
	
	/**
	 * @param ParserInterface $parser
	 */
	public function __construct(ParserInterface $parser = null)
	{
		$this->log = ObjectRepository::getLogger($this);
		
		if ( ! is_null($parser)) {
			$this->setParser($parser);
		}
	}
	
	/**
	 * @return ParserInterface
	 */
	public function getParser()
	{
		return $this->parser;
	}

	/**
	 * @param ParserInterface $parser
	 */
	public function setParser(ParserInterface $parser)
	{
		$this->parser = $parser;
	}
	
	/**
	 * Loads configuration file
	 * @param string $configurationFile
	 */
	public function loadFile($configurationFile)
	{
		$this->configurationFile = $configurationFile;
		
		if (is_null($this->parser)) {
			throw new Exception\RuntimeException("Parser not assigned to configuration loader");
		}
		
		$data = null;
		
		try {
			$data = $this->parser->parseFile($configurationFile);
		} catch (Exception\ConfigurationException $e) {
			throw new Exception\InvalidConfiguration("Configuration file "
					. $this->configurationFile 
					. " could not be parsed", null, $e);
		}
		
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

			if ($object instanceof ConfigurationInterfaceParserCallback) {
				$object->setLoader($this);
			}
			
			foreach ($properties as $propertyName => $propertyValue) {
				// For now ignoring the setter function matching
//				$possibleSetterName = 'set' . ucfirst($propertyName);
				
				if (property_exists($className, $propertyName)) {
					$object->$propertyName = $this->processItem($propertyValue);
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
			throw new Exception\InvalidConfiguration("Configuration file contents "
					. $this->configurationFile 
					. " could not be processed as component configuration", null, $e);
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

	/**
	 * Get last loaded file path
	 *
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->configurationFile;
	}
	
}
