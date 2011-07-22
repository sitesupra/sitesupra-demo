<?php

namespace Supra\FileStorage;

/**
 * File storage
 *
 */
class FileStorage
{

	/**
	 * Object instance
	 * @var object
	 */
	protected static $instance;

	/**
	 * File Storage internal path
	 * @var string
	 */
	public $internalPath = null;

	/**
	 * File Storage external path
	 * @var string
	 */
	public $externalPath = null;

	/**
     * Protecting from new FileStorage
     * @return FileStorage
     */
	private function __construct(){}

	/**
     * Protecting from cloning
     * @return FileStorage
     */
	private function __clone(){}

	/**
	 * Magic setter and getter method
	 * @param string $name
	 * @param array $arguments
	 */
	public function __call($name, $arguments)
	{
		// prefix set or get
		$prefix = \substr($name, 0, 3);
		$property = \strtolower(\substr($name, 3, 1)) . \substr($name, 4);


		// checking for property presence
		if (\property_exists($this, $property)) {
			if ($prefix == 'set') {
				// checking for arguments presence
				if (empty($arguments)) {
					throw new Exception('Arguments can\'t be empty');
				} else {
					$this->setValue($property, $arguments);
				}
			} else if ($prefix == 'get') {
				return $this->getValue($property);
			} else {
				throw new \Exception('Unknown action "' . $prefix . '". Supported only set and get');
			}
		} else {
			throw new \Exception('There is no such property as "' . $property . '" in FileStorage object');
		}
	}

	/**
	 * Returning only one instance of object
	 * @return FileStorage
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new FileStorage;
		}
		return self::$instance;
	}

	/**
	 * Setter method
	 * @param string $property
	 * @param array $arguments
	 */
	private function setValue($property, $arguments = null)
	{
		if (\count($arguments) == 1) {
			$this->$property = $arguments[0];
		} else {
			$this->$property = $arguments;
		}
	}

	/**
	 * Getter method
	 * @param string $property
	 * @return mixed
	 */
	private function getValue($property)
	{
		return $this->$property;
	}

	/**
	 * FileStorage
	 */
//  checkWebSafe() using Upload Filters
//  deleteFile($fileObj)
//  deleteFolder($fileObj) only empty folders
//  storeUploadedFile
//  LIST (children by folder id)
// getDoctrineRepository()
//  setPrivate (File $file)
//  setPublic (File $file)
// getFile($fileId)
// getFolder($fileId)
// getFileContents(File $file)
// getFileHandle(File $file)
// setDbConnection

	/**
	 *
	 * @param \Supra\FileStorage\Entity\File $file
	 * @param <type> $source 
	 * @autowired
	 */
	private static $log; // Logger

	function storeFileData(\Supra\FileStorage\Entity\File $file, $source)
	{
//		SupraDatabase::getConnection(__CLASS__);
//		$log = Logger::getLogger(__CLASS__);
//		Repository::getInstance('Logger', __CLASS__);

		\Log::debug();

		$file->setSize();

		$dest = $file->getPath();

		copy($source, $dest);
	}

}