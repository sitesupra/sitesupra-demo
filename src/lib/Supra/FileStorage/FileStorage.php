<?php

namespace Supra\FileStorage;

use Supra\FileStorage\Validation;
use Supra\FileStorage\Helpers;
use Supra\FileStorage\Entity;
use Supra\FileStorage\Exception;

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
	private function __construct() {
		
	}

	/**
	 * Protecting from cloning
	 * @return FileStorage
	 */
	private function __clone() {
		
	}

	/**
	 * Folder access mode
	 * @var integer chmod
	 */
	private $folderAccessMode = 0750;

	/**
	 * File access mode
	 * @var integer chmod
	 */
	private $fileAccessMode = 0640;

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
	 * Set folder access mode
	 * @param integer $folderAccessMode chmod
	 */
	public function setFolderAccessModeInFileSystem($folderAccessMode)
	{
		$this->folderAccessMode = $folderAccessMode;
	}

	/**
	 * Set file access mode
	 * @param integer $fileAccessMode chmod
	 */
	public function setFileAccessModeInFileSystem($fileAccessMode)
	{
		$this->fileAccessMode = $fileAccessMode;
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

	/**
	 * Get Doctrine entity manager
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getEntityManager()
	{
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();
	}

	// TODO: deleteFile($fileObj)
	// TODO: deleteFolder($fileObj) only empty folders
	// TODO: LIST (children by folder id)
	// TODO: getDoctrineRepository()
	// TODO: getFile($fileId)
	// TODO: getFolder($fileId)
	// TODO: getFileContents(File $file)
	// TODO: getFileHandle(File $file)
	// TODO: setDbConnection

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

		$filePath = $this->getExternalPath() . DIRECTORY_SEPARATOR . $destination;

		if ( ! copy($sourceFilePath, $filePath)) {
			throw new Exception\RuntimeException('Failed to copy file form "' . $sourceFilePath . '" to "' . $destination . '"');
		} else {
			chmod($filePath, $this->fileAccessMode);
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

		if ($file->isPublic()) {
			$filePath = $this->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		} else {
			$filePath = $this->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		}

		$file->setName($filename);

		try {
			$newExtension = $file->getExtension();

			if ($oldExtension != $newExtension) {
				throw new Exception\UploadFilterException('You can\'t change file extension');
			}

			foreach ($this->fileUploadFilters as $filter) {
				$filter->validateFile($file);
			}

			$this->renameFileInFileSystem($file, $filename, $filePath);

		} catch (Exception\RuntimeException $exception) {
			$file->setName($oldFileName);
			throw $exception;
		} catch (Exception\UploadFilterException $exception) {
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
			$newPath = dirname($path) . DIRECTORY_SEPARATOR . $filename;
			$result = rename($path, $newPath);
			if ($result) {
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
			
		} catch (Exception\RuntimeException $exception) {
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
			$newPath = dirname($path) . DIRECTORY_SEPARATOR . $title;
			$result = rename($path, $newPath);
			if ($result) {
				$folder->setName($title);
			}
		} else {
			throw new Exception\RuntimeException($path . ' is not a folder');
		}
	}

	/**
	 * Creates new folder in all file storages
	 * @param string $folderName
	 * @return true or throws Exception\RuntimeException
	 */
	public function createFolder($destination, $folderName = '')
	{
		$fileNameHelper = new Helpers\FileNameValidationHelper();
		$result = $fileNameHelper->validate($folderName);

		if( ! $result) {
			throw new Exception\UploadFilterException($fileNameHelper->getErrorMessage());
		}

		if (( ! empty($folderName)) && ( ! empty($destination))) {
			$folderName = DIRECTORY_SEPARATOR . $folderName;
		}

		$internalPath = $this->getInternalPath() . $destination . $folderName;
		$externalPath = $this->getExternalPath() . $destination . $folderName;

		$internalFolderResult = $this->createFolderInFileSystem($internalPath);
		$externalFolderResult = $this->createFolderInFileSystem($externalPath);

		if ($internalFolderResult && $externalFolderResult) {
			return true;
		} else {
			throw new Exception\RuntimeException('Something went wrong while creating folder');
		}
	}

	/**
	 * Actual folder creation function which is triggered by $this->createFolder();
	 * @param string $fullPath
	 * @return true or throws Exception\RuntimeException
	 */
	private function createFolderInFileSystem($fullPath)
	{
		$externalPath = $this->getExternalPath();
		$internalPath = $this->getInternalPath();

		if (($fullPath != $externalPath) && ($fullPath != $internalPath)) {
			if ( ! is_dir($fullPath)) {
				if (mkdir($fullPath, $this->folderAccessMode)) {
					return true;
				} else {
					throw new Exception\RuntimeException('Could not create folder in ' . $fullPath);
				}
			} else {
				throw new Exception\RuntimeException('Folder with such name already exists');
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

	/**
	 * Moves file or folder to public storage
	 * @param Entity\Abstraction\File $file
	 * @param boolean $public true by default. If public == false moves file to private storage
	 */
	public function setPublic(Entity\Abstraction\File $file, $public = true)
	{

		if ($file instanceof Entity\File) {
			$this->setPublicForFile($file, $public);
		} else if ($file instanceof Entity\Folder) {
			$this->setPublicForFolder($file, $public);
		} else {
			throw new Exception\RuntimeException('Wrong entity passed');
		}
	}

	/**
	 * Moves file or folder to private storage
	 * @param Entity\Abstraction\File $file
	 */
	public function setPrivate(Entity\Abstraction\File $file)
	{
		$this->setPublic($file, false);
	}

	/**
	 * Moves file to public storage if $public is true. Otherwise moves to private.
	 * @param Entity\File $file
	 * @param boolean $public
	 */
	private function setPublicForFile(Entity\File $file, $public)
	{

		$filePath = $file->getPath(DIRECTORY_SEPARATOR, true);

		if ($public) {
			if ($file->isPublic()) {
				\Log::info($file->getId() . ' ' . $file->getName() . ' is already public');
			} else {
				$this->moveFileToExternalStorage($filePath);
				$file->setPublic(true);
			}
		} else {
			if ($file->isPublic()) {
				$this->moveFileToInternalStorage($filePath);
				$file->setPublic(false);
			} else {
				\Log::info($file->getId() . '#' . $filePath . ' is already private');
			}
		}
	}

	/**
	 * Moves folder to public storage if $public is true. Otherwise moves to private.
	 * @param Entity\Folder $folder
	 * @param boolean $public
	 */
	private function setPublicForFolder(Entity\Folder $folder, $public)
	{
		$children = $folder->getChildren();
		1+1;
//		throw new FileStorageException('Not done yet');
	}

	/**
	 * Actual file move to external storage
	 * @param string $filePath
	 */
	private function moveFileToExternalStorage($filePath)
	{
		$oldPath = $this->getInternalPath() . $filePath;
		$newPath = $this->getExternalPath() . $filePath;

		if (!rename($oldPath, $newPath)) {
			throw new Exception\RuntimeException('Failed to move file to the public storage');
		}
	}

	/**
	 * Actual file move to internal storage
	 * @param string $filePath
	 */
	private function moveFileToInternalStorage($filePath)
	{
		$oldPath = $this->getExternalPath() . $filePath;
		$newPath = $this->getInternalPath() . $filePath;

		if (!rename($oldPath, $newPath)) {
			throw new Exception\RuntimeException('Failed to move file to the private storage');
		}
	}

}