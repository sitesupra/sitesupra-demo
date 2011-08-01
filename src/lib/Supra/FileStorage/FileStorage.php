<?php

namespace Supra\FileStorage;

use Supra\FileStorage\UploadFilter;
use Supra\FileStorage\Helpers;
use Supra\FileStorage\Entity;

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
	 * Upload file filters array for processing
	 * @var array
	 */
	private $fileUploadFilters = array();

	/**
	 * Upload folder filters array for processing
	 * @var array
	 */
	private $folderUploadFilters = array();
	
	/**
	 * $_FILES['error'] messages
	 * TODO: separate messages to MediaLibrary UI and to Logger
	 * @var array
	 */
	public $fileUploadErrorMessages = array(
		'1' => 'The uploaded file exceeds the maximum upload file size',
		'2' => 'The uploaded file exceeds the maximum upload file size',
		'3' => 'The uploaded file was only partially uploaded',
		'4' => 'No file was uploaded',
		'6' => 'Missing a temporary folder',
		'7' => 'Failed to write file to disk',
		'8' => 'A PHP extension stopped the file upload',
	);

	/**
	 * Protecting from new FileStorage
	 * @return FileStorage
	 */
	private function __construct()
	{
		
	}

	/**
	 * Protecting from cloning
	 * @return FileStorage
	 */
	private function __clone()
	{
		
	}

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
	 * Add file upload filter
	 *
	 * @param \Supra\Validation\FileValidationInterface $filter
	 */
	public function addFileUploadFilter($filter)
	{
		$this->fileUploadFilters[] = $filter;
	}

	/**
	 * Add folder upload filter
	 *
	 * @param \Supra\Validation\FolderValidationInterface $filter
	 */
	public function addFolderUploadFilter($filter)
	{
		$this->folderUploadFilters[] = $filter;
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
		foreach ($this->fileUploadFilters as $filter) {
			$filter->validateFile($file);
		}

		// get dir path
		$destination = $file->getPath(DIRECTORY_SEPARATOR, false);

		// get full dest path
		$destination .= DIRECTORY_SEPARATOR . $file->getName();

		// copy
		// TODO: copy to internal and external
		if ( ! copy($sourceFilePath, $this->getInternalPath() . DIRECTORY_SEPARATOR . $destination)) {
			throw new FileStorageException('Failed to copy file form "' . $sourceFilePath . '" to "' . $destination . '"');
		}
	}

	/**
	 * Rename file in all file storages
	 * @param Entity\File $file
	 * @param string $filename new file name
	 */
	public function renameFile(Entity\File $file, $filename)
	{
		$oldExtension = $file->getExtension();
		$oldFileName = $file->getName();

		//TODO: $file->getFilesFileStorage() @return internal/external
		$internalPath = $this->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$externalPath = $this->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		$file->setName($filename);

		try {
			$newExtension = $file->getExtension();

			if ($oldExtension != $newExtension) {
				throw new FileStorageException('You can\'t change file extension');
			}

			foreach ($this->fileUploadFilters as $filter) {
				$filter->validateFile($file);
			}

			$this->renameFileInFileSystem($file, $filename, $internalPath);
			$this->renameFileInFileSystem($file, $filename, $externalPath);
		} catch (FileStorageException $exception) {
			$file->setName($oldFileName);
			throw $exception;
		}
	}

	/**
	 * Actual file rename which is triggered by $this->renameFile();
	 * @param Entity\File $file
	 * @param string $filename new file name
	 * @param string $path
	 * @return \Supra\FileStorage\Entity\File
	 */
	private function renameFileInFileSystem(Entity\File $file, $filename, $path)
	{
		if (file_exists($path)) {
			if (rename($path, dirname($path) . DIRECTORY_SEPARATOR . $filename)) {
				$file->setName($filename);
			}
		}
		//TODO: Pass message to Media Library?
//		else {
//			throw new FileStorageException('File does not exists in ' . $path);
//		}
	}

	/**
	 * Rename folder in all file storages
	 * @param Entity\Folder $folder
	 * @param string $title new folder name
	 */
	public function renameFolder(Entity\Folder $folder, $title)
	{
		$internalPath = $this->getInternalPath() . $folder->getPath(DIRECTORY_SEPARATOR, true);
		$externalPath = $this->getExternalPath() . $folder->getPath(DIRECTORY_SEPARATOR, true);
		// old folder name for rollback if validation fails
		$oldFolderName = $folder->getName();

		$folder->setName($title);

		try {
			// validating folder before renaming
			foreach ($this->folderUploadFilters as $filter) {
				$filter->validateFolder($folder);
			}

			// rename folder in both file storages
			$this->renameFolderInFileSystem($folder, $title, $internalPath);
			$this->renameFolderInFileSystem($folder, $title, $externalPath);
		} catch (FileStorageException $exception) {
			$folder->setName($oldFolderName);
			throw $exception;
		}
	}

	/**
	 * Actual folder rename which is triggered by $this->renameFolder();
	 * @param Entity\Folder $folder
	 * @param string $title new folder name
	 * @param string $path
	 */
	private function renameFolderInFileSystem(Entity\Folder $folder, $title, $path)
	{
		if (is_dir($path)) {
			if (rename($path, dirname($path) . DIRECTORY_SEPARATOR . $title)) {
				$folder->setName($title);
			}
		} else {
			throw new FileStorageException($path . ' is not a folder');
		}
	}

	/**
	 * Creates new folder in all file storages
	 * @param string $folderName
	 * @return true or throws FileStorageException
	 */
	public function createFolder($destination, $folderName = '')
	{
		$fileNameHelper = new Helpers\FileNameValidationHelper();
		$fileNameHelper->validate($folderName);

		if (( ! empty($folderName)) && ( ! empty($destination))) {
			$folderName = DIRECTORY_SEPARATOR . $folderName;
		}

		$internal = $this->createFolderInFileSystem($this->getInternalPath() . $destination . $folderName);
		$external = $this->createFolderInFileSystem($this->getExternalPath() . $destination . $folderName);

		if (($external === true) && ($internal === true)) {
			return true;
		} else {
			throw new FileStorageException('Something went wrong while creating folder');
		}
	}

	/**
	 * Actual folder creation function which is triggered by $this->createFolder();
	 * @param string $fullPath
	 * @return true or throws FileStorageException
	 */
	private function createFolderInFileSystem($fullPath)
	{
		// mkdir
		// FIXME chmod

		$externalPath = $this->getExternalPath();
		$internalPath = $this->getInternalPath();

		if (($fullPath != $externalPath) && ($fullPath != $internalPath)) {
			if ( ! is_dir($fullPath)) {
				if (mkdir($fullPath, 0777)) {
					return true;
				} else {
					throw new FileStorageException('Could not create folder in ' . $fullPath);
				}
			} else {
				throw new FileStorageException('Folder with such name already exists');
			}
		} else {
			return true;
		}
	}

	/**
	 * Returns file extension
	 * @param string $filename
	 * @return string
	 */
	private function getExtension(string $filename)
	{
		$fileinfo = pathinfo($filename);
		$extension = $fileinfo['extension'];

		return $extension;
	}

}