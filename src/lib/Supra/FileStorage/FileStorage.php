<?php

namespace Supra\FileStorage;

use Supra\FileStorage\Validation;
use Supra\FileStorage\Helpers;
use Supra\FileStorage\Entity;
use Supra\FileStorage\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Supra\Log\Writer\WriterAbstraction;

/**
 * File storage
 *
 */
class FileStorage
{

	const CACHE_GROUP_NAME = 'Supra\FileStorage';
	const RESERVED_DIR_SIZE = "_size";
	const RESERVED_DIR_VERSION = "_ver";
	const VALIDATION_EXTENSION_RENAME_MESSAGE_KEY = 'medialibrary.validation_error.extension_rename';
	const VALIDATION_IMAGE_TO_FILE_REPLACE_MESSAGE_KEY = 'medialibrary.validation_error.image_to_file';
	const MISSING_IMAGE_PATH = '/cms/lib/supra/build/medialibrary/assets/skins/supra/images/icons/broken-image.png';
	const FILE_INFO_EXTERNAL = 1;
	const FILE_INFO_INTERNAL = 2;

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
	 * Url base for files residing in external path
	 * @var string
	 */
	protected $externalUrlBase = null;

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
	 * @return EntityManager
	 */
	public function getDoctrineEntityManager()
	{
		return ObjectRepository::getEntityManager($this);
	}

	/**
	 * @return WriterAbstraction
	 */
	public function log()
	{
		return ObjectRepository::getLogger($this);
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
	 * Sets external (public) file url base.
	 * @param string $urlBase 
	 */
	public function setExternalUrlBase($urlBase)
	{
		$this->externalUrlBase = $urlBase;
	}

	/**
	 * Gets external (public) file url base.
	 * @param string $urlBase 
	 */
	public function getExternalUrlBase()
	{
		return $this->externalUrlBase;
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
	 * @param string $internalPath 
	 */
	public function setInternalPathAbsolute($internalPath)
	{
		$this->internalPath = rtrim($internalPath, '\/') . DIRECTORY_SEPARATOR;
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
	 * @param string $externalPath 
	 */
	public function setExternalPathAbsolute($externalPath)
	{
		$this->externalPath = rtrim($externalPath, '\/') . DIRECTORY_SEPARATOR;
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
	 * Add file upload filter
	 * @param Validation\FileValidationInterface $filter
	 */
	public function addFileUploadFilter(Validation\FileValidationInterface $filter)
	{
		$this->fileUploadFilters[] = $filter;
	}

	/**
	 * Add folder upload filter
	 * @param Validation\FolderValidationInterface $filter
	 */
	public function addFolderUploadFilter(Validation\FolderValidationInterface $filter)
	{
		$this->folderUploadFilters[] = $filter;
	}

	/**
	 * Validates against filters
	 * @param Entity\File $file
	 */
	public function validateFileUpload(Entity\File $file, $sourceFilePath = null)
	{
		// file validation
		foreach ($this->fileUploadFilters as $filter) {
			/* @var $filter Validation\FileValidationInterface */
			$filter->validateFile($file, $sourceFilePath);
		}
	}

	/**
	 * Store file data
	 *
	 * @param Entity\File $file
	 * @param string $source
	 */
	public function storeFileData(Entity\File $file, $sourceFilePath)
	{
		$this->validateFileUpload($file, $sourceFilePath);

		$this->createBothFoldersInFileSystem($file);

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
	 * @param string $fileName new file name
	 * @throws Exception\UploadFilterException on not valid change
	 */
	public function renameFile(Entity\File $file, $fileName)
	{
		$entityManager = $this->getDoctrineEntityManager();

		$newFile = clone($file);
		$entityManager->detach($newFile);

		$oldName = $file->getFileName();

		$newFile->setFileName($fileName);

		$oldExtension = $file->getExtension();
		$newExtension = $newFile->getExtension();

		if ($oldExtension != $newExtension) {
			throw new Exception\UploadFilterException(self::VALIDATION_EXTENSION_RENAME_MESSAGE_KEY, 'You can\'t change file extension');
		}

		$this->validateFileUpload($newFile);

		$this->renameFileInFileSystem($file, $fileName);

		$entityManager->merge($newFile);
		$entityManager->flush();

		// to track title changes in audit
		$file->setFileName($oldName);
		$file->setFileName($fileName);
	}

	/**
	 * Actual file rename which is triggered by $this->renameFile();
	 * @param Entity\File $file
	 * @param string $filename new file name
	 */
	private function renameFileInFileSystem(Entity\File $file, $filename)
	{
		$path = $this->getFilesystemPath($file);

		if (file_exists($path)) {

			$newPath = dirname($path) . DIRECTORY_SEPARATOR . $filename;
			$result = rename($path, $newPath);

			if ($result) {

				if ($file instanceof Entity\Image) {
					$sizes = $file->getImageSizeCollection();
					if ( ! $sizes->isEmpty()) {
						foreach ($sizes as $size) {
							$sizeName = $size->getName();
							$filePath = $this->getImagePath($file, $sizeName);
							$newPath = dirname($filePath) . DIRECTORY_SEPARATOR . $filename;
							rename($filePath, $newPath);
						}
					}
				}
			} else {
				throw new Exception\RuntimeException('File renaming failed');
			}
		}
		//TODO: Pass message to Media Library?
//		else {
//			throw new Exception\RuntimeException('File does not exist in ' . $path);
//		}
	}

	/**
	 * Rename folder in all file storages.
	 * Doesn't involve moving the folder in another folder.
	 * @param Entity\Folder $folder
	 * @param string $newTitle new folder name
	 */
	public function renameFolder(Entity\Folder $folder, $newTitle)
	{
		$entityManager = $this->getDoctrineEntityManager();

		$newFolder = clone($folder);
		$entityManager->detach($newFolder);
		$newFolder->setFileName($newTitle);

		// validating folder before renaming
		foreach ($this->folderUploadFilters as $filter) {
			$filter->validateFolder($newFolder);
		}

		// rename folder in both file storages
		$this->renameFolderInFileSystem($folder, $newFolder);

		$entityManager->merge($newFolder);
		$entityManager->flush();
	}

	/**
	 * Actual folder rename which is triggered by $this->renameFolder();
	 * @param Entity\Folder $folder
	 * @param Entity\Folder $newFolder new folder data
	 * @param string $path
	 */
	private function renameFolderInFileSystem(Entity\Folder $folder, Entity\Folder $newFolder)
	{
		// rename folder in both file storages
		$externalPath = $this->getExternalPath();
		$internalPath = $this->getInternalPath();

		foreach (array($externalPath, $internalPath) as $basePath) {

			$oldFullPath = $basePath . $folder->getPath(DIRECTORY_SEPARATOR, true);
			$newFullPath = $basePath . $newFolder->getPath(DIRECTORY_SEPARATOR, true);

			// Should not happen
			if ($oldFullPath === $newFullPath) {
				continue;
			}

			if (is_dir($oldFullPath)) {

				$result = rename($oldFullPath, $newFullPath);

				if ( ! $result) {
					throw new Exception\RuntimeException("Failed to rename folder from '$oldFullPath' to '$newFullPath'");
				}
			} else {
				$this->log()->warn("Folder '$oldFullPath' missing in filesystem on rename");
				$this->createFolderInFileSystem($basePath, $newFolder);
			}
		}
	}

	/**
	 * Creates new folder in all file storages
	 * @param string $destination
	 * @param Entity\Folder $folder 
	 */
	public function createFolder(Entity\Folder $folder)
	{
		// validating folder before creation
		foreach ($this->folderUploadFilters as $filter) {
			$filter->validateFolder($folder);
		}

		$this->createBothFoldersInFileSystem($folder);
	}

	/**
	 * Creates the filesystem folder in both storages -- internal and external
	 * @param Entity\Abstraction\File $folder
	 */
	private function createBothFoldersInFileSystem(Entity\Abstraction\File $folder = null)
	{
		if ($folder instanceof Entity\File) {
			$folder = $folder->getParent();
		}

		$this->createFolderInFileSystem($this->getExternalPath(), $folder);
		$this->createFolderInFileSystem($this->getInternalPath(), $folder);
	}

	/**
	 * Actual folder creation function which is triggered by $this->createFolder();
	 * @param string $basePath
	 * @param Entity\Folder $folder
	 * @return true or throws Exception\RuntimeException
	 */
	private function createFolderInFileSystem($basePath, Entity\Folder $folder = null)
	{
		$destination = '';
		if ( ! is_null($folder)) {
			$destination = $folder->getPath(DIRECTORY_SEPARATOR, true);
		}

		$fullPath = $basePath . $destination;

		if ( ! is_dir($fullPath)) {

			if (file_exists($fullPath)) {
				throw new Exception\RuntimeException('Could not create folder in '
						. $fullPath . ', file exists with the same name');
			}

			if (mkdir($fullPath, $this->folderAccessMode, true)) {
				return true;
			} else {
				throw new Exception\RuntimeException('Could not create folder in ' . $fullPath);
			}
		}
	}

	/**
	 * Returns file extension
	 * @param string $filename
	 * @return string
	 */
	private function getExtension($filename)
	{
		$extension = pathinfo($filename, PATHINFO_EXTENSION);

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
		if ($public == $file->isPublic()) {
			$msg = $file->getId() . ' ' . $file->getFileName() . ' is already ';
			$msg .= ($file->isPublic() ? 'public' : 'private');
			$this->log()->info($msg);
			return;
		}

		$fileList = array();

		// prepare list of files to be moved
		$fileList[] = $file->getPath(DIRECTORY_SEPARATOR, true);
		if ($file instanceof Entity\Image) {
			$sizes = $file->getImageSizeCollection();
			if ( ! $sizes->isEmpty()) {
				$fileDir = $file->getPath(DIRECTORY_SEPARATOR, false)
						. DIRECTORY_SEPARATOR
						. self::RESERVED_DIR_SIZE . DIRECTORY_SEPARATOR;

				foreach ($sizes as $size) {

					$sizeDir = $fileDir . DIRECTORY_SEPARATOR
							. $size->getFolderName() . DIRECTORY_SEPARATOR;

					$externalPath = $this->getExternalPath() . $sizeDir;
					$this->createFolderInFileSystem($externalPath);

					$internalPath = $this->getInternalPath() . $sizeDir;
					$this->createFolderInFileSystem($internalPath);

					$fileList[] = $fileDir . DIRECTORY_SEPARATOR
							. $size->getFolderName() . DIRECTORY_SEPARATOR
							. $file->getFileName();
				}
			}
		}

		$folder = $file->getParent();

		if ($public) {
			foreach ($fileList as $filePath) {
				$this->moveFileToExternalStorage($filePath, $folder);
			}
			$file->setPublic(true);
		} else {
			foreach ($fileList as $filePath) {
				$this->moveFileToInternalStorage($filePath, $folder);
			}
			$file->setPublic(false);
		}

		$file->setModificationTime();
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

			if ($node instanceof Entity\Folder) {
				$node->setPublic($public);
			}
		}
		$folder->setPublic($public);
	}

	/**
	 * Actual file move to external storage
	 * @param string $filePath
	 * @param Entity\Folder $folder
	 */
	private function moveFileToExternalStorage($filePath, Entity\Folder $folder = null)
	{
		$oldPath = $this->getInternalPath() . $filePath;
		$newPath = $this->getExternalPath() . $filePath;

		$this->createBothFoldersInFileSystem($folder);

		if ( ! rename($oldPath, $newPath)) {
//			throw new Exception\RuntimeException('Failed to move file to the public storage');
			$filename = basename($newPath);
			$this->log()->warn('Failed to move file (' . $filename . ') to the public storage');
		}
	}

	/**
	 * Actual file move to internal storage
	 * @param string $filePath
	 * @param Entity\Folder $folder
	 */
	private function moveFileToInternalStorage($filePath, Entity\Folder $folder = null)
	{
		$oldPath = $this->getExternalPath() . $filePath;
		$newPath = $this->getInternalPath() . $filePath;

		$this->createBothFoldersInFileSystem($folder);

		if ( ! rename($oldPath, $newPath)) {
			// throw new Exception\RuntimeException('Failed to move file to the private storage');
			$filename = basename($newPath);
			$this->log()->warn('Failed to move file (' . $filename . ') to the private storage');
		}
	}

	/**
	 * Move file or folder
	 * 
	 * @param Entity\Abstraction\File $entity
	 * @param Entity\Abstraction\File $target or null
	 * 
	 * @throws Exception\RuntimeException
	 */
	public function move(Entity\Abstraction\File $entity, $target = null)
	{
		$entityManager = $this->getDoctrineEntityManager();
		$entityManager->beginTransaction();

		try {
			// move entity in database
			$entityManager->persist($entity);

			$otherStorageOldPath = null;
			if ($entity instanceof Entity\Folder) {
				if ($entity->isPublic()) {
					$otherStorageOldPath = $this->getFilesystemPath($entity, true, self::FILE_INFO_INTERNAL);
				} else {
					$otherStorageOldPath = $this->getFilesystemPath($entity, true, self::FILE_INFO_EXTERNAL);
				}
			}

			$oldPath = $this->getFilesystemPath($entity);

			if ($target instanceof Entity\Folder) {
				$entity->moveAsLastChildOf($target);
			} else {
				$rootLevelFolder = $entityManager->getRepository(Entity\Abstraction\File::CN())
						->findOneBy(array('level' => 0));

				if ( ! $rootLevelFolder instanceof Entity\Abstraction\File) {
					throw new Exception\RuntimeException('Failed to find root level file');
				}

				$entity->moveAsNextSiblingOf($rootLevelFolder);
			}

			$entityManager->flush();


			$otherStorageNewPath = null;
			if ($entity instanceof Entity\Folder) {
				if ($entity->isPublic()) {
					$otherStorageNewPath = $this->getFilesystemPath($entity, true, self::FILE_INFO_INTERNAL);
				} else {
					$otherStorageNewPath = $this->getFilesystemPath($entity, true, self::FILE_INFO_EXTERNAL);
				}
			}

			$newPath = $this->getFilesystemPath($entity);

			if ( ! rename($oldPath, $newPath)) {
				if ($entity instanceof Entity\Folder) {
					$this->log()->error("Failed to move '{$oldPath}' to '{$newPath}' in filesystem");
					$this->createFolderInFileSystem($newPath);
				} else {
					throw new Exception\RuntimeException('Failed to move in filesystem');
				}
			}

			// move file/folder from opposite storage in  file system
			if ($entity instanceof Entity\Folder) {
				if ( ! rename($otherStorageOldPath, $otherStorageNewPath)) {
					$this->log()->error("Failed to move folder from '{$otherStorageOldPath}' to '{$otherStorageNewPath}' in filesystem");
					$this->createFolderInFileSystem($otherStorageNewPath);
					//throw new Exception\RuntimeException('Failed to move in filesystem');
				}
			}

			$entityManager->commit();
		} catch (\Exception $e) {
			$entityManager->rollback();
			throw $e;
		}

		// return entity back
		return $entity;
	}

	/**
	 * Create resized version for the image
	 * @param Entity\Image $file
	 * @param integer $targetWidth
	 * @param integer $targetHeight
	 * @param boolean $cropped 
	 * @param integer $quality
	 * @param boolean $force
	 * @return string
	 */
	public function createResizedImage(Entity\Image $file, $targetWidth, $targetHeight, $cropped = false, $quality = 95, $force = false)
	{
		// validate params
		if ( ! $file instanceof Entity\Image) {
			throw new Exception\RuntimeException('Image entity expected');
		}
		if (($targetWidth <= 0) || ($targetHeight <= 0)) {
			throw new Exception\RuntimeException('Dimensions are invalid');
		}

		$sizeName = $this->getImageSizeName($targetWidth, $targetHeight, $cropped);

		if ( ! $this->fileExists($file)) {
			$this->log()->warn("Image '{$file->getFileName()}' is missing in the filesystem, tried to resize to {$sizeName}");

			return $sizeName;
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

		$resizedFilePath = $resizedFileDir . DIRECTORY_SEPARATOR . $file->getFileName();
		$resizer->setOutputFile($resizedFilePath);
		$resizer->process();

		$entityManager = $this->getDoctrineEntityManager();

		$entityManager->persist($size);
		$entityManager->flush();

		return $sizeName;
	}

	/**
	 * Returns image size name, based on image height, weight and cropped flag
	 * @param integer $targetWidth
	 * @param integer $targetHeight
	 * @param boolean $cropped
	 * @return string 
	 */
	public function getImageSizeName($targetWidth, $targetHeight, $cropped = false)
	{
		$sizeName = $targetWidth . 'x' . $targetHeight;
		if ($cropped) {
			$sizeName .= 'cropped';
		}

		return $sizeName;
	}

	/**
	 * Recreate all existing resized versions of the image
	 * @param Entity\Image $file 
	 */
	protected function recreateImageSizes(Entity\Image $file)
	{
		if ( ! $file instanceof Entity\Image) {
			throw new Exception\RuntimeException('Image entity expected');
		}

		$sizes = $file->getImageSizeCollection();
		if ( ! $sizes->isEmpty()) {
			foreach ($sizes as $size) {
				$sizeName = $size->getName();
				$filePath = $this->getImagePath($file, $sizeName);

				if (file_exists($filePath)) {
					$result = unlink($filePath);

					if ( ! $result) {
						throw new Exception\RuntimeException("Could not delete '$filePath' from file storage");
					}
				}

				$this->createResizedImage($file, $size->getTargetWidth(), $size->getTargetHeight(), $size->getCropMode(), $size->getQuality(), true);
			}
		}
	}

	/**
	 * Rotate image
	 * @param Entity\Image $file
	 * @param integer $rotationCount
	 * @param integer $quality
	 */
	public function rotateImage(Entity\Image $file, $rotationCount, $quality = 95)
	{
		if ( ! $file instanceof Entity\Image) {
			throw new Exception\RuntimeException('Image entity expected');
		}

		$filename = $this->getFilesystemPath($file);
		$rotator = new ImageProcessor\ImageRotator;
		$rotator->setSourceFile($filename)
				->setOutputFile($filename)
				->setOutputQuality($quality)
				->setRotationCount($rotationCount);
		$rotator->rotate();

		if (($rotationCount % 2) == 1) {
			$tmp = $file->getWidth();
			$file->setWidth($file->getHeight());
			$file->setHeight($tmp);

			$entityManager = $this->getDoctrineEntityManager();
			$entityManager->persist($file);
			$entityManager->flush();
		}

		$this->recreateImageSizes($file);
	}

	/**
	 * Rotate image by 90 degrees CCW
	 * @param Entity\Image $file
	 * @param integer $quality 
	 */
	public function rotateImageLeft(Entity\Image $file, $quality = 95)
	{
		$this->rotateImage($file, ImageProcessor\ImageRotator::ROTATE_LEFT, $quality);
	}

	/**
	 * Rotate image by 90 degrees CW
	 * @param Entity\Image $file
	 * @param integer $quality 
	 */
	public function rotateImageRight(Entity\Image $file, $quality = 95)
	{
		$this->rotateImage($file, ImageProcessor\ImageRotator::ROTATE_RIGHT, $quality);
	}

	/**
	 * Rotate image by 180
	 * @param Entity\Image $file
	 * @param integer $quality 
	 */
	public function rotateImage180(Entity\Image $file, $quality = 95)
	{
		$this->rotateImage($file, ImageProcessor\ImageRotator::ROTATE_180, $quality);
	}

	/**
	 * Crop image
	 * @param Entity\Image $file
	 * @param integer $left
	 * @param integer $right
	 * @param integer $width
	 * @param integer $height
	 * @param integer $quality 
	 */
	public function cropImage(Entity\Image $file, $left, $top, $width, $height, $quality = 95)
	{
		if ( ! $file instanceof Entity\Image) {
			throw new Exception\RuntimeException('Image entity expected');
		}

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

		$file->setWidth($width);
		$file->setHeight($height);

		$entityManager = $this->getDoctrineEntityManager();
		$entityManager->persist($file);
		$entityManager->flush();

		$this->recreateImageSizes($file);
	}

	/**
	 * Get mime type of file
	 * @param string $filename
	 * @return string
	 */
	public function getMimeType($filename)
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
	 * Check whether MIME describes image file type or not
	 * @param string $mimetype
	 * @return boolean
	 */
	public function isMimeTypeImage($mimetype)
	{
		$isImage = strpos($mimetype, 'image/');

		if ($isImage === 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get full file path or its directory (with trailing slash)
	 * @param Entity\Abstraction\File $file
	 * @param boolean $dirOnly 
	 * @param integer $forcePath Forces external or internal path. Use FILE_INFO_EXTERNAL and FILE_INFO_INTERNAL constants
	 * @return string
	 */
	public function getFilesystemPath(Entity\Abstraction\File $file, $includeFilename = true, $forcePath = null)
	{
		if ( ! $file instanceof Entity\Abstraction\File) {
			throw new Exception\RuntimeException('File or folder entity expected');
		}

		$path = $this->getInternalPath();

		if ($forcePath != self::FILE_INFO_INTERNAL) {
			if ($file->isPublic() || $forcePath == self::FILE_INFO_EXTERNAL) {
				$path = $this->getExternalPath();
			}
		}

		$path .= $file->getPath(DIRECTORY_SEPARATOR, false);
		$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if ($includeFilename) {
			$path .= $file->getFileName();
		}

		return $path;
	}

	/**
	 * Get file directory (with trailing slash)
	 * @param Entity\File $file
	 * @return string
	 */
	public function getFilesystemDir(Entity\File $file)
	{
		return $this->getFilesystemPath($file, false);
	}

	/**
	 * Get full file path for image size
	 * @param Entity\Image $file
	 * @param string $sizeName 
	 */
	public function getImagePath(Entity\Image $file, $sizeName = null)
	{
		if ( ! $file instanceof Entity\Image) {
			throw new Exception\RuntimeException('Image entity expected');
		}

		$path = $this->getFilesystemDir($file);
		$size = $file->findImageSize($sizeName);
		if ($size instanceof Entity\ImageSize) {
			$path .= self::RESERVED_DIR_SIZE . DIRECTORY_SEPARATOR
					. $size->getFolderName() . DIRECTORY_SEPARATOR
					. $file->getFileName();
		} else {
			$path .= $file->getFileName();
		}
		return $path;
	}

	/**
	 * Get web (external) path for file
	 * @param Entity\File $file
	 * @param Entity\ImageSize $size | string $size
	 * @return string 
	 */
	public function getWebPath(Entity\File $file, $size = null)
	{
		if ( ! $file instanceof Entity\File) {
			throw new Exception\RuntimeException('File or folder entity expected');
		}

		if ($file instanceof Entity\Image && isset($size)) {
			if ( ! $size instanceof Entity\ImageSize) {
				$size = $file->findImageSize($size);
			}
		} else {
			$size = null;
		}

		if ($file->isPublic()) {

			$path = $file->getPathEntity()->getWebPath();

			$pathParts = explode('/', $path);
			array_pop($pathParts);

			// Get file storage url base in webroot
			$path = '/' . trim(implode('/', $pathParts), '/\\') . '/';
			if ($size instanceof Entity\ImageSize) {
				$path .= self::RESERVED_DIR_SIZE . '/'
						. $size->getFolderName() . '/';
			}

			// Encode the filename URL part
			$path .= rawurlencode($file->getFileName());

			return $path;
		}

		return null;
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

	//FIXME: pass required parameters as arguments not an array (tmp_name and name)
	public function replaceFile(Entity\File $fileEntity, $file)
	{
		$entityManager = $this->getDoctrineEntityManager();
		$oldFileIsImage = $fileEntity instanceof Entity\Image;
		$newFileIsImage = $this->isMimeTypeImage($file['type']);

		if ($oldFileIsImage !== $newFileIsImage) {
			throw new Exception\UploadFilterException(self::VALIDATION_IMAGE_TO_FILE_REPLACE_MESSAGE_KEY, 'New file should be image too');
		}

		$originalFile = clone($fileEntity);
		$entityManager->detach($originalFile);

		// setting new data
		// The name is not changed by Bug #6756
//		$fileEntity->setFileName($file['name']);
		$fileEntity->setSize($file['size']);
		$fileEntity->setMimeType($file['type']);

		// This must be call before removing the old file
		$this->validateFileUpload($fileEntity, $file['tmp_name']);

		$this->removeFileInFileSystem($originalFile);

		$this->storeFileData($fileEntity, $file['tmp_name']);

		// additional jobs for images
		if ($fileEntity instanceof Entity\Image) {
			// store original size
			$imageProcessor = new ImageProcessor\ImageResizer();
			$imageInfo = $imageProcessor->getImageInfo($this->getFilesystemPath($fileEntity));
			$fileEntity->setWidth($imageInfo['width']);
			$fileEntity->setHeight($imageInfo['height']);
			// reprocess sizes
			$this->recreateImageSizes($fileEntity);
		}

		$fileEntity->setModificationTime();

		$entityManager->flush();
	}

	/**
	 * Remove file in file system
	 * @param Entity\File $file 
	 */
	private function removeFileInFileSystem(Entity\File $file)
	{
		$filePath = $this->getFilesystemPath($file);

		if (file_exists($filePath)) {
			$result = unlink($filePath);

			if ( ! $result) {
				throw new Exception\RuntimeException("Could not delete '$filePath' from file storage");
			}
		}

		// remove sizes if object is image
		if ($file instanceof Entity\Image) {
			$sizes = $file->getImageSizeCollection();
			foreach ($sizes as $size) {
				$sizePath = $this->getImagePath($file, $size->getName());

				if (file_exists($sizePath)) {
					$result = unlink($sizePath);

					if ( ! $result) {
						throw new Exception\RuntimeException("Could not delete '$sizePath' from file storage");
					}
				}
			}
		}
	}

	protected function fireFileEvent($type, $args)
	{
		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire($type, $args);
	}

	/**
	 * Remove file or folder from database and system
	 * @param Entity\Abstraction\File $entity 
	 */
	public function remove(Entity\Abstraction\File $entity)
	{
		$eventArgs = new FileEventArgs($this);
		$eventArgs->setFile($entity);
		$this->fireFileEvent(FileEventArgs::FILE_EVENT_PRE_DELETE, $eventArgs);

		if ($entity instanceof Entity\Folder) {
			$hasChildren = $entity->hasChildren();
			if ($hasChildren) {
				throw new Exception\RuntimeException('You can remove only empty folders');
			}
			$this->removeFolder($entity);
		} elseif ($entity instanceof Entity\File) {
			$this->removeFile($entity);
		} else {
			throw new Exception\LogicException('Not recognized file type passed: ' . get_class($entity));
		}
	}

	/**
	 * Remove folder from database and file system
	 * @param Entity\Folder $folder 
	 */
	private function removeFolder(Entity\Folder $folder)
	{
		$this->removeFolderInFileSystem($folder);

		$entityManager = $this->getDoctrineEntityManager();
		$entityManager->remove($folder);
		$entityManager->flush();
	}

	/**
	 * Remove folder in file system
	 * @param Entity\Folder $folder
	 */
	private function removeFolderInFileSystem(Entity\Folder $folder)
	{
		$folderPath = $folder->getPath(DIRECTORY_SEPARATOR, true);

		$folderExternalPath = $this->getExternalPath() . $folderPath;
		$folderInternalPath = $this->getInternalPath() . $folderPath;

		// we are ignoring that one of the folders might not exist
		$resultInternal = @rmdir($folderInternalPath);
		$resultExternal = @rmdir($folderExternalPath);
	}

	/**
	 * Remove file from database and file system
	 * @param Entity\File $file 
	 */
	private function removeFile(Entity\File $file)
	{
		$this->removeFileInFileSystem($file);

		$entityManager = $this->getDoctrineEntityManager();
		$entityManager->remove($file);
		$entityManager->flush();
	}

	/**
	 * Loads item info array
	 * @param Entity\Abstraction\File $file
	 * @param string $locale
	 * @return array
	 */
	public function getFileInfo(Entity\Abstraction\File $file, $locale = null)
	{
		$info = $file->getInfo($locale);

		if ($file instanceof Entity\File) {
			$filePath = $this->getWebPath($file);
			$info['file_web_path'] = $filePath;

			if ($file instanceof Entity\Image) {

				//CMS need to know, if image still exists in file storage
				$fileExists = $this->fileExists($file);
				$info['exists'] = $fileExists;
				if ( ! $fileExists) {
					$info['missing_path'] = self::MISSING_IMAGE_PATH;
				}

				foreach ($info['sizes'] as $sizeName => &$size) {

					$sizePath = null;

					// TODO: original size is also as size, such skipping is ugly
					if ($sizeName == 'original') {
						$sizePath = $filePath;
					} else {
						$sizePath = $this->getWebPath($file, $sizeName);
					}

					$size['external_path'] = $sizePath;
				}
			}
		}

		// Generate folder ID path
		$parents = $file->getAncestors(0, false);
		$parents = array_reverse($parents);
		$path = array(0);

		foreach ($parents as $parent) {
			array_push($path, $parent->getId());
		}

		$info['path'] = $path;

		return $info;
	}

	/**
	 * Retuns folder access mode like "0750"
	 * @return string 
	 */
	public function getFolderAccessMode()
	{
		return $this->folderAccessMode;
	}

	/**
	 * Retuns file access mode like "0640"
	 * @return string 
	 */
	public function getFileAccessMode()
	{
		return $this->fileAccessMode;
	}

	/**
	 * Checks if the file exists
	 * @param Entity\File $file
	 * @return boolean
	 */
	public function fileExists(Entity\Abstraction\File $file)
	{
		$path = $this->getFilesystemPath($file);
		$fileExists = file_exists($path);

		return $fileExists;
	}

	public function calculateImageSizeFromHeight($originalWidth, $originalHeight, $expectedHeight)
	{
		$newWidth = null;
		if ($originalWidth > $originalHeight) {
			$newWidth = round(($originalHeight / $originalWidth) * $expectedHeight);
		} else {
			$newWidth = round(($originalWidth / $originalHeight) * $expectedHeight);
		}

		return array(
			'height' => $expectedHeight,
			'width' => $newWidth,
		);
	}

	public function calculateImageSizeFromWidth($originalWidth, $originalHeight, $expectedWidth)
	{
		$newHeight = null;
		if ($originalWidth > $originalHeight) {
			$newHeight = round(($originalWidth / $originalHeight) * $expectedWidth);
		} else {
			$newHeight = round(($originalHeight / $originalWidth) * $expectedWidth);
		}

		return array(
			'height' => $newHeight,
			'width' => $expectedWidth,
		);
	}

}
