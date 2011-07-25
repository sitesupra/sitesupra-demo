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
	 * Default file storage internal/external
	 * @var string
	 */
	protected $defaultStorage = 'external';
	
    /**
    * Upload filters array for processing
    * @var array
    */
	private $uploadFilters = array();
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
	 * Get file storage internal directory path
	 * 
	 * @return string
	 */
	public function getInternalPath()
	{
		return $this->internalPath;
	}

	/**
	 * Set file storage internal directory
	 * 
	 * @param string $internalPath
	 */
	public function setInternalPath($internalPath)
	{
		$internalPath = rtrim($internalPath, '\/') . DIRECTORY_SEPARATOR;
		$this->internalPath = SUPRA_PATH . $internalPath;
	}

	/**
	 * Get file storage external directory path
	 * 
	 * @return string
	 */
	public function getExternalPath()
	{
		return $this->externalPath;
	}

	/**
	 * Set file storage external directory
	 * 
	 * @param string $externalPath
	 */
	public function setExternalPath($externalPath)
	{
		$externalPath = rtrim($externalPath, '\/') . DIRECTORY_SEPARATOR;
		$this->externalPath = SUPRA_WEBROOT_PATH . $externalPath;
	}

	/**
	 * Returning only one instance of object
	 * 
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
	 * Add upload filter
	 *
	 * @param \Supra\Validation\UploadFilterInterface $filter
	 */
	public function addUploadFilter($filter)
	{
		$this->uploadFilters[] = $filter;
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

	/**
	 * Store file data
	 *
	 * @param \Supra\FileStorage\Entity\File $file
	 * @param string $source 
	 */
	function storeFileData($file, $sourceFilePath)
	{
		// file validation
		foreach ($this->uploadFilters as $filter) {
			$filter->validate($file);
		}
		
		// get dir path
		// TODO external/internal switch
		$dest = $this->getInternalPath() . 
				$file->getPath(DIRECTORY_SEPARATOR, false);
		
		// mkdir
		// FIXME chmod
		$mkDirResult = mkdir($dest, 0777, true);
		
		// get full dest path
		$dest .= DIRECTORY_SEPARATOR . $file->getName();
		
		// copy
		copy($sourceFilePath, $dest);
	}
}