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
		$destination = $file->getPath(DIRECTORY_SEPARATOR, false);

		$mkDirResult = $this->createFolder($destination);

		// get full dest path
		$destination .= DIRECTORY_SEPARATOR . $file->getName();

		// copy
		// TODO: copy to internal and external
		if( ! copy($sourceFilePath, $this->getInternalPath(). DIRECTORY_SEPARATOR . $destination)) {
			throw new FileStorageException('Failed to copy file form "'.$sourceFilePath.'" to "'.$destination.'"');
		}
	}

	/**
	 * Rename file in all file storages
	 * @param \Supra\FileStorage\Entity\File $file
	 * @param string $filename new file name
	 * @return \Supra\FileStorage\Entity\File
	 */
	public function renameFile(&$file, $filename)
	{
		//TODO: $file->getFilesFileStorage() @return internal/external
		$internalPath = $this->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$externalPath = $this->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		$file = $this->_renameFile($file, $filename, $internalPath);
		$file = $this->_renameFile($file, $filename, $externalPath);

		return $file;
	}

	/**
	 * Actual file rename which is triggered by $this->renameFile();
	 * @param \Supra\FileStorage\Entity\File $file
	 * @param string $filename new file name
	 * @param string $path
	 * @return \Supra\FileStorage\Entity\File
	 */
	private function _renameFile(&$file, $filename, $path) {
		if (file_exists($path)) {
			if (rename($path, dirname($path). DIRECTORY_SEPARATOR . $filename)) {
				$file->setName($filename);
			}
		}
		//TODO: Pass message to Media Library?
//		else {
//			throw new FileStorageException('File does not exists in ' . $path);
//		}

		return $file;
	}

	/**
	 * Rename folder in all file storages
	 * @param \Supra\FileStorage\Entity\Folder $folder
	 * @param string $title new folder name
	 * @return \Supra\FileStorage\Entity\Folder
	 */
	public function renameFolder(&$folder, $title)
	{
		$internalPath = $this->getInternalPath() . $folder->getPath(DIRECTORY_SEPARATOR, true);
		$externalPath = $this->getExternalPath() . $folder->getPath(DIRECTORY_SEPARATOR, true);

		$folder = $this->_renameFolder($folder, $title, $internalPath);
		$folder = $this->_renameFolder($folder, $title, $externalPath);

		return $folder;
	}

	/**
	 * Actual folder rename which is triggered by $this->renameFolder();
	 * @param \Supra\FileStorage\Entity\Folder $folder
	 * @param string $title new folder name
	 * @param string $path
	 * @return \Supra\FileStorage\Entity\Folder
	 */
	private function _renameFolder(&$folder, $title, $path)
	{
		if (is_dir($path)) {
			if (rename($path, dirname($path). DIRECTORY_SEPARATOR . $title)) {
				$folder->setName($title);
			}
		} else {
			throw new FileStorageException($path . ' is not a folder');
		}

		return $folder;
	}

	/**
	 * Creates new folder in all file storages
	 * @param string $folder
	 * @return true or throws FileStorageException
	 */
	public function createFolder($folder)
	{
		if ( ! empty($folder)) {
			$internal = $this->_createFolder($this->getInternalPath() . $folder);
			$external = $this->_createFolder($this->getExternalPath() . $folder);

			if (($external === true) && ($internal === true)) {
				return true;
			} else {
				throw new FileStorageException('Something went wrong while creating folder');
			}
		} else {
			throw new FileStorageException('Destination is empty');
		}
	}

	/**
	 * Actual folder creation function which is triggered by $this->createFolder();
	 * @param string $fullPath
	 * @return true or throws FileStorageException
	 */
	private function _createFolder($fullPath)
	{
		// mkdir
		// FIXME chmod
		if ( ! is_dir($fullPath)) {
			if (mkdir($fullPath, 0777)) {
				return true;
			} else {
				throw new FileStorageException('Could not create folder in ' . $fullPath);
			}
		} else {
			return true;
		}
	}

}