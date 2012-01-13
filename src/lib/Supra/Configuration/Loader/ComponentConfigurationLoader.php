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
	 * Lowest caching level, means no cache at all
	 */
	const CACHE_LEVEL_NO_CACHE = 0;
	
	/**
	 * Caching level which assumes, that cache will expire 
	 * on file modification time change
	 */
	const CACHE_LEVEL_EXPIRE_BY_MODIFICATION = 1;
	
	/**
	 * Highest caching level, means no cache expiring at all
	 */
	const CACHE_LEVEL_NO_EXPIRE = 2;
	
	
	const CACHE_NAMESPACE = 'conf_';
	
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
	 * @var integer
	 */
	protected $cacheLevel = self::CACHE_LEVEL_NO_CACHE;
	
	/**
	 * @var MemcacheCache
	 */
	private $cacheAdapter;
	
	/**
	 * @param ParserInterface $parser
	 */
	public function __construct(ParserInterface $parser = null)
	{
		$this->log = ObjectRepository::getLogger($this);
		
		if ( ! is_null($parser)) {
			$this->setParser($parser);
		}
		
		$this->cacheAdapter = ObjectRepository::getCacheAdapter($this);
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
		
		$data = $this->getCachedData($configurationFile);
		
		if (empty($data)) {
			try {
				$data = $this->parser->parseFile($configurationFile);
			} catch (Exception\ConfigurationException $e) {
				throw new Exception\InvalidConfiguration("Configuration file "
						. $this->configurationFile 
						. " could not be parsed", null, $e);
			}
			
			$this->storeData($data);
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
	
	/**
	 * Returns cached data for $fileName if caching is enabled and cache exists
	 * 
	 * @param string $id
	 * @return mixed
	 */
	protected function getCachedData($fileName)
	{
		if ($this->cacheLevel == self::CACHE_LEVEL_NO_CACHE) {
			return null;
		}
		
		$id = $this->_getCacheIdByName($fileName);
		$data = $this->cacheAdapter->fetch($id);
		
		return $data;
	}


	/**
	 * Store parsed config array
	 * 
	 * @param array $data
	 * @return boolean
	 */
	protected function storeData($data)
	{
		if ($this->cacheLevel == self::CACHE_LEVEL_NO_CACHE) {
			return;
		}
		
		$id = $this->_getCacheIdByName($this->configurationFile);

		return $this->cacheAdapter->save($id, $data);
	}
	
	/**
	 * @param int $level 
	 */
	public function setCacheLevel($level)
	{
		$this->cacheLevel = $level;
	}
	
	/**
	 * Helper method to get unique string for config file
	 * @return string
	 */
	private function _getCacheIdByName($fileName) 
	{
		$modificationTime = null;
		if ($this->cacheLevel == self::CACHE_LEVEL_EXPIRE_BY_MODIFICATION && is_readable($fileName)) {
			$modificationTime = filemtime($fileName);
		}
		
		return md5( self::CACHE_NAMESPACE . $fileName . $modificationTime );
	} 
	
}
