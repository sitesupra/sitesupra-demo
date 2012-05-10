<?php

namespace Supra\Configuration\Loader;

use Supra\Configuration\Parser\ParserInterface;
use Supra\Configuration\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Loader;
use Supra\Configuration\ConfigurationInterface;

/**
 * Component configuration loader
 */
class ComponentConfigurationLoader
{

	const KEY_NAME_VERSION = '_version';
	const KEY_NAME_USE = '_use';
	const KEY_NAME_CLASS = '_class';
	const KEY_NAME_ARRAY = '_array';
	const KEY_NAME_ITEMS = '_items';
	

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

	/**
	 * @var WriterAbstraction
	 */
	protected $log;

	/**
	 * @var integer
	 */
	protected $version = 1;

	/**
	 * @var array
	 */
	protected $uses = array();

	/**
	 * @var integer
	 */
	protected $level = 0;
	protected $itemNumber = -1;

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
	 * @var Doctrine\Common\Cache\Cache
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

		if (is_null($data)) {
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
	 * Process item
	 *
	 * @param mixed $item
	 * @return mixed
	 */
	protected function processItem($item)
	{
		$this->itemNumber ++;

		$return = $item;

		if (is_array($item) && (count($item) == 1)) {

			$value = end($item);
			$key = key($item);

			if ($key === 'const') {

				if ( ! is_string($value)) {
					throw new \Exception("Constant name value not a string - " . var_export($value, 1) . var_export($item, 1));
				}

				if (defined($value)) {
					$return = constant($value);
				} else {
					throw new \Exception("Constant $value not found");
				}
			} else if ($key === self::KEY_NAME_VERSION) {

				$this->version = $value;
			} else if ($key === self::KEY_NAME_USE) {

				if ($this->version < 2) {
					throw new \Exception('Functionality of "_use" is not available in this version.');
				}

				$parts = explode('\\', $value);

				if (empty($parts)) {
					throw new \Exception('Could not process namespace "' . $value . '" for "_use".');
				} else {

					$name = array_pop($parts);

					if (isset($this->uses[$name])) {
						throw new \Exception('Conflicting namespaces for same short name: "' . $this->uses[$name] . '" and "' . $value . '".');
					} else {
						$this->uses[$name] = $value;
					}
				}
			} else if ($key === self::KEY_NAME_CLASS) {

				if ($this->version < 2) {
					throw new \Exception('Functionality of "_class" is not available in this version.');
				}
			} else if (is_string($key) && is_array($value)) {
				// try to setup config object

				$object = $this->processObject($key, $value);

				if (is_object($object)) {
					$return = $object;
				}
			}
		} else if (is_array($item) && isset($item[self::KEY_NAME_CLASS])) {

			$className = $item[self::KEY_NAME_CLASS];

			unset($item[self::KEY_NAME_CLASS]);

			$object = $this->processObject($className, $item);

			if (is_object($object)) {
				$return = $object;
			}
		} 
		
		if (is_array($return)) {
			foreach ($return as &$subitem) {

				$this->level ++;

				$currentItemNumber = $this->itemNumber;
				$this->itemNumber = 0;

				$subitem = $this->processItem($subitem);

				$this->itemNumber = $currentItemNumber;
				$this->level --;
			}
		}

		// Replacement values
		if (is_string($return)) {
			if (strpos($return, '${') !== false) {

				// only the current directory for now
				$return = str_replace('${__DIR__}', dirname($this->getFilePath()), $return);
			}
		}

		return $return;
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

			if ( ! Loader\Loader::classExists($className) && ! empty($this->uses)) {

				$classNameParts = explode('\\', $className);

				$firstNamePart = array_shift($classNameParts);

				if (isset($this->uses[$firstNamePart])) {

					array_unshift($classNameParts, $this->uses[$firstNamePart]);
					$className = join('\\', $classNameParts);
				}
			}

			/* @var $object \Supra\Configuration\ConfigurationInterface */
			$object = Loader\Loader::getClassInstance($className, 'Supra\Configuration\ConfigurationInterface');

			if ($object instanceof LoaderRequestingConfigurationInterface) {
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

		$id = $this->getCacheIdByName($fileName);
		$data = $this->cacheAdapter->fetch($id);

		return ($data === false ? null : $data);
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

		$id = $this->getCacheIdByName($this->configurationFile);

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
	 * Helper method to get unique id string for config file
	 * @return string
	 */
	private function getCacheIdByName($fileName)
	{
		$modificationTime = null;
		if ($this->cacheLevel == self::CACHE_LEVEL_EXPIRE_BY_MODIFICATION && is_readable($fileName)) {
			$modificationTime = filemtime($fileName);
		}

		return md5(__CLASS__ . $fileName . $modificationTime);
	}

}
