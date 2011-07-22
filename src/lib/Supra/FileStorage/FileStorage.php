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
	protected $internalPath = null;

	/**
	 * File Storage external path
	 * @var string
	 */
	protected $externalPath = null;

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
	 * @return string
	 */
	public function getInternalPath()
	{
		return $this->internalPath;
	}

	/**
	 * @param string $internalPath
	 */
	public function setInternalPath($internalPath)
	{
		$this->internalPath = $internalPath;
	}

	/**
	 * @return string
	 */
	public function getExternalPath()
	{
		return $this->externalPath;
	}

	/**
	 * @param string $externalPath
	 */
	public function setExternalPath($externalPath)
	{
		$this->externalPath = $externalPath;
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