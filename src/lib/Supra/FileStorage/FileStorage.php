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

	const
		RESERVED_DIR_SIZE = "_size",
		RESERVED_DIR_VERSION = "_ver";

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
	function storeFileData(Entity\File $file, $sourceFilePath)
	{
		// file validation
		foreach ($this->fileUploadFilters as $filter) {
			$filter->validateFile($file);
		}

		$filePath = $this->getFilesystemPath($file);

		if ( ! copy($sourceFilePath, $filePath)) {
			throw new Exception\RuntimeException('Failed to copy file form "' . $sourceFilePath . '" to "' . $filePath . '"');
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

		$filePath = $this->getFilesystemPath($file);

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
				$timeNow = new \DateTime('now');
				$file->setModifiedTime($timeNow);
				$file->setName($filename);
			}
		}
		//TODO: Pass message to Media Library?
//		else {
//			throw new FileStorageException('File does not exist in ' . $path);
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

				$timeNow = new \DateTime('now');

				$folder->setModifiedTime($timeNow);
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

		if ( ! $result) {
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
	private function getExtension($filename)
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
		
		$timeNow = new \DateTime('now');
				
		if ($public) {
			if ($file->isPublic()) {
				\Log::info($file->getId() . ' ' . $file->getName() . ' is already public');
			} else {
				$this->moveFileToExternalStorage($filePath);
				$file->setPublic(true);
				$file->setModifiedTime($timeNow);
			}
		} else {
			if ($file->isPublic()) {
				$this->moveFileToInternalStorage($filePath);
				$file->setPublic(false);
				$file->setModifiedTime($timeNow);
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
		$descendants = $folder->getDescendants();
		foreach ($descendants as $node) {
			if ($node instanceof Entity\File) {
				$this->setPublicForFile($node, $public);
			}
		}
	}

	/**
	 * Actual file move to external storage
	 * @param string $filePath
	 */
	private function moveFileToExternalStorage($filePath)
	{
		$oldPath = $this->getInternalPath() . $filePath;
		$newPath = $this->getExternalPath() . $filePath;

		if ( ! rename($oldPath, $newPath)) {
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

		if ( ! rename($oldPath, $newPath)) {
			throw new Exception\RuntimeException('Failed to move file to the private storage');
		}
	}

	/**
	 * Create resized version for the image
	 * @param File $file
	 * @param integer $targetWidth
	 * @param integer $targetHeight
	 * @param boolean $cropped 
	 */
	public function createResizedImage($file, $targetWidth, $targetHeight, $cropped = false, $quality = 95, $force = false)
	{
		// validate params
		if ( ! $file instanceof Entity\File) {
			throw new Exception\RuntimeException('File entity expected');
		}
		if (($targetWidth <= 0) || ($targetHeight <= 0)) {
			throw new Exception\RuntimeException('Dimensions are invalid');
		}

		// FIXME move to seperate helper-method somewhere
		$sizeName = $targetWidth . 'x' . $targetHeight;
		if ($cropped) {
			$sizeName .= 'cropped';
		}

		$size = $file->getImageSize($sizeName);
		
		if (($size->getTargetHeight() == $targetHeight)
			&& ($size->getTargetWidth() == $targetWidth)
			&& ($size->getQuality() == $quality)
			&& ($size->getCropMode() == $cropped)
			&& empty($force)
		) {
			// nothing to update
			return $sizeName;
		}
		
		$size->setQuality($quality);
		$size->setCropMode($cropped);
		$size->setTargetWidth($targetWidth);
		$size->setTargetHeight($targetHeight);
		
		$originalFilePath = $this->getFilesystemPath($file, true);
		
		// initiate resizer
		$resizer = new ImageProcessor\ImageResizer;
		$resizer->setSourceFile($originalFilePath)
				->setOutputQuality($quality)
				->setTargetWidth($targetWidth)
				->setTargetHeight($targetHeight)
				->setCropMode($cropped);
		
		$expectedSize = $resizer->getExpectedSize();
		$size->setWidth($expectedSize['width']);
		$size->setHeight($expectedSize['height']);

		$resizedFileDir = $this->getFilesystemDir($file)
				. self::RESERVED_DIR_SIZE . DIRECTORY_SEPARATOR 
				. $size->getFolderName();

		if ( ! file_exists($resizedFileDir)) {
			$mkdirResult = mkdir($resizedFileDir, $this->folderAccessMode, true);
			if (empty($mkdirResult)) {
				throw new Exception\RuntimeException(
						'Could not create directory for resized image');
			}
		}

		$resizedFilePath = $resizedFileDir . DIRECTORY_SEPARATOR . $file->getName();
		$resizer->setOutputFile($resizedFilePath);
		$resizer->process();
		
		$this->getEntityManager()->persist($size);
		$this->getEntityManager()->flush();

		return $sizeName;
	}

	/**
	 * Rotate image
	 * @param Entity\File $file
	 * @param integer $rotationCount
	 * @param integer $quality
	 */
	public function rotateImage($file, $rotationCount, $quality = 95) 
	{
		$filename = $this->getFilesystemPath($file);
		$rotator = new ImageProcessor\ImageRotator;
		$rotator->setSourceFile($filename)
				->setOutputFile($filename)
				->setOutputQuality($quality)
				->setRotationCount($rotationCount);
		$rotator->rotate();		

		$sizes = $file->getImageSizeCollection();
		if ( ! $sizes->isEmpty()) {
			foreach ($sizes as $size) {
				$sizeName = $size->getName();
				
				// FIXME what to do with original?
				if ($sizeName == 'original') {
					if (($rotationCount % 2) == 1) {
						$tmp = $size->getWidth();
						$size->setWidth($size->getHeight());
						$size->setHeight($tmp);
						$this->getEntityManager()->persist($size);
						$this->getEntityManager()->flush();
					}
					continue;
				}
				
				$filePath = $this->getImagePath($file, $sizeName);
				unlink($filePath);
				$this->createResizedImage($file, $size->getTargetWidth(), 
						$size->getTargetHeight(), $size->getCropMode(), 
						$size->getQuality(), true);
			}
		}
	}

	/**
	 * Rotate image by 90 degrees CCW
	 * @param Entity\File $file
	 * @param integer $quality 
	 */
	public function rotateImageLeft($file, $quality = 95) 
	{
		$this->rotateImage($file, ImageProcessor\ImageRotator::ROTATE_LEFT, $quality);
	}
	
	/**
	 * Rotate image by 90 degrees CW
	 * @param Entity\File $file
	 * @param integer $quality 
	 */
	public function rotateImageRight($file, $quality = 95) 
	{
		$this->rotateImage($file, ImageProcessor\ImageRotator::ROTATE_RIGHT, $quality);
	}

	/**
	 * Rotate image by 180
	 * @param Entity\File $file
	 * @param integer $quality 
	 */
	public function rotateImage180($file, $quality = 95) 
	{
		$this->rotateImage($file, ImageProcessor\ImageRotator::ROTATE_180, $quality);
	}

	/**
	 * Crop image
	 * @param Entity\File $file
	 * @param integer $left
	 * @param integer $right
	 * @param integer $width
	 * @param integer $height
	 * @param integer $quality 
	 */
	public function cropImage(Entity\File $file, $left, $top, $width, $height, $quality = 95) 
	{
		$filename = $this->getFilesystemPath($file);
		$cropper = new ImageProcessor\ImageCropper();
		$cropper->setSourceFile($filename)
				->setOutputFile($filename)
				->setOutputQuality($quality)
				->setLeft($left)
				->setTop($top)
				->setWidth($width)
				->setHeight($height);
		$cropper->process();

		$sizes = $file->getImageSizeCollection();
		if ( ! $sizes->isEmpty()) {
			foreach ($sizes as $size) {
				$sizeName = $size->getName();
				
				// FIXME what to do with original?
				if ($sizeName == 'original') {
					$size->setWidth($width);
					$size->setHeight($height);
					$this->getEntityManager()->persist($size);
					$this->getEntityManager()->flush();
					continue;
				}
				
				$filePath = $this->getImagePath($file, $sizeName);
				unlink($filePath);
				$this->createResizedImage($file, $size->getTargetWidth(), 
						$size->getTargetHeight(), $size->getCropMode(), 
						$size->getQuality(), true);
			}
		}
	}

	/**
	 * Get mime type of file
	 * @param string $filename
	 * @return string
	 */
	public function getMimeType(string $filename)
	{
		if ( ! file_exists($filename)) {
			throw new Exception\RuntimeException('File does not exist');
		}
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $filename);
		finfo_close($finfo);
		return $mimeType;
	}

	/**
	 * Get full file path or its directory (with trailing slash)
	 * @param Entity\Abstraction\File $file
	 * @param boolean $dirOnly 
	 * @return string
	 */
	public function getFilesystemPath(Entity\Abstraction\File $file, $includeFilename = true)
	{
		if ( ! $file instanceof Entity\Abstraction\File) {
			throw new Exception\RuntimeException('File or folder entity expected');
		}
		$path = $this->getInternalPath();
		if ($file->isPublic()) {
			$path = $this->getExternalPath();
		}
		$path .= $file->getPath(DIRECTORY_SEPARATOR, false);
		$path .= DIRECTORY_SEPARATOR;
		if ($includeFilename) {
			$path .= $file->getName();
		}
		return $path;
	}

	/**
	 * Get file directory (with trailing slash)
	 * @param Entity\Abstraction\File $file
	 * @return string
	 */
	public function getFilesystemDir(Entity\Abstraction\File $file)
	{
		return $this->getFilesystemPath($file, false);
	}

	/**
	 * Get full file path for image size
	 * @param Entity\File $file
	 * @param string $sizeName 
	 */
	public function getImagePath(Entity\File $file, $sizeName = null)
	{
		// FIXME what to do with original?
		if ($sizeName == 'original') {
			$sizeName = null;
		}
		$path = $this->getFilesystemDir($file);
		$size = $file->getImageSize($sizeName);
		if ($size instanceof Entity\ImageSize) {
			$path .= self::RESERVED_DIR_SIZE . DIRECTORY_SEPARATOR
					. $size->getFolderName() . DIRECTORY_SEPARATOR
					. $file->getName();
		} else {
			$path .= $file->getName();
		}
		return $path;
	}

	/**
	 * Get web (external) path for file
	 * @param Entity\File $file
	 * @param type $sizeName
	 * @return type 
	 */
	public function getWebPath(Entity\File $file, $sizeName = null)
	{
		if ( ! $file instanceof Entity\File) {
			throw new Exception\RuntimeException('File or folder entity expected');
		}

		// FIXME what to do with original?
		if ($sizeName == 'original') {
			$sizeName = null;
		}

		if ($file->isPublic()) {
			$path = '/'
					. str_replace(SUPRA_WEBROOT_PATH, '', $this->getExternalPath());
			$path .= $file->getPath('/', false) . '/';
			
			if ($file->isMimeTypeImage() || isset($sizeName)) {
				$size = $file->findImageSize($sizeName);
				if ($size instanceof Entity\ImageSize) {
					$path .= self::RESERVED_DIR_SIZE . '/'
							. $size->getFolderName() . '/';
				}
			}
			
			$path .= $file->getName();
			return $path;
		} else {
			// TODO implement for private files
		}
	}

	/**
	 * Get file content
	 * @param Entity\File $file
	 * @return string
	 */
	public function getFileContent(Entity\File $file)
	{
		$filePath = $this->getFilesystemPath($file);
		$fileContent = file_get_contents($filePath);
		return $fileContent;
	}

	public function replaceFile(Entity\File $fileEntity, $file)
	{

		$oldMimeType = $fileEntity->getMimeType();
		$newMimeType = $file['type'];

		$oldFileIsImage = $fileEntity->isMimeTypeImage($oldMimeType);
		$newFileIsImage = $fileEntity->isMimeTypeImage($newMimeType);

		if ($oldFileIsImage) {
			if ( ! $newFileIsImage) {
				throw new Exception\UploadFilterException('New file should be image too');
			}
		}
		
		// TODO: change to versioning
		$this->removeFile($fileEntity);
		
		// setting new data
		$fileEntity->setName($file['name']);
		$fileEntity->setSize($file['size']);
		$fileEntity->setMimeType($file['type']);
		
		
		$timeNow = new \DateTime('now');
		$fileEntity->setModifiedTime($timeNow);
				
		$this->storeFileData($fileEntity, $file['tmp_name']);

		// additional jobs for images
		if ($fileEntity->isMimeTypeImage()) {
			// store original size
			$origSize = $fileEntity->getImageSize('original');
			$imageProcessor = new ImageProcessor\ImageResizer();
			$imageInfo = $imageProcessor->getImageInfo($this->getFilesystemPath($fileEntity));
			$origSize->setWidth($imageInfo['width']);
			$origSize->setHeight($imageInfo['height']);
			// reprocess sizes
			$sizes = $fileEntity->getImageSizeCollection();
			if ( ! $sizes->isEmpty()) {
				foreach ($sizes as $size) {
					$sizeName = $size->getName();

					// FIXME what to do with original?
					if ($sizeName == 'original') {
						continue;
					}

					$filePath = $this->getImagePath($fileEntity, $sizeName);
					unlink($filePath);
					$this->createResizedImage($fileEntity, $size->getTargetWidth(), 
							$size->getTargetHeight(), $size->getCropMode(), 
							$size->getQuality(), true);
				}
			}
		}
	}

	/**
	 * Remove file
	 * @param Entity\File $file 
	 */
	public function removeFile(Entity\File $file)
	{
		$filePath = $this->getFilesystemPath($file);

		$fileExists = file_exists($filePath);
		
		$result = false;
		
		if ($fileExists) {
			
			$result = unlink($filePath);
			
		} else {
			throw new Exception\RuntimeException('File doesn\'t exist in file storage');
		}
		
		if ( ! $result) {
			throw new Exception\RuntimeException('Failed to delete file from file storage');
		}
	}

}