<?php

namespace Supra\Package\Cms\FileStorage;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\Entity\Abstraction\File as FileAbstraction;;
use Supra\Package\Cms\Entity\Folder;
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\ImageSize;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * File storage
 */
class FileStorage implements ContainerAware
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
	 * @var ContainerInterface
	 */
	protected $container;

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
	 * File custom property configurations array
	 * @var array
	 */
	protected $customPropertyConfigurations = array();
	
	/**
	 * @var Path\FilePathGeneratorInterface 
	 */
	protected $filePathGenerator;
	
	/**
	 * Folder access mode
	 * @var integer chmod
	 */
	private $folderAccessMode = 0755;

	/**
	 * File access mode
	 * @var integer chmod
	 */
	private $fileAccessMode = 0644;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @return EntityManager
	 */
	public function getDoctrineEntityManager()
	{
		return $this->container->getDoctrine()->getManager();
	}

	/**
	 * @return WriterAbstraction
	 */
	public function log()
	{
		return ObjectRepository::getLogger($this);
	}

	/**
	 * @param string $fileId
	 * @param string $type
	 * @return null|\Supra\FileStorage\File
	 */
	public function find($fileId, $type = null)
	{
		if ( ! is_string($fileId)) {
			return null;
		}

		if (empty($type)) {
			$type = Entity\Abstraction\File::CN();
		}
		
		return $this->getDoctrineEntityManager()
				->find($type, $fileId);
	}

	/**
	 * Gets external (public) file url base.
	 */
	public function getExternalUrlBase()
	{
		return '/files/';
	}

	/**
	 * Get file storage internal directory path
	 *
	 * @return string
	 */
	public function getInternalPath()
	{
		return $this->container->getParameter('directories.storage') . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Get file storage external directory path
	 *
	 * @return string
	 */
	public function getExternalPath()
	{
		return $this->container->getParameter('directories.web') . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
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
	 * @param File $file
	 */
	public function validateFileUpload(File $file, $sourceFilePath = null)
	{
		// file validation
		foreach ($this->fileUploadFilters as $filter) {
			/* @var $filter Validation\FileValidationInterface */
			$filter->validateFile($file, $sourceFilePath);
		}
	}
	
	/**
	 * @param Path\FilePathGeneratorInterface $generator
	 */
	public function setFilePathGenerator(Path\FilePathGeneratorInterface $generator)
	{
		$this->filePathGenerator = $generator;
	}
	
	/**
	 * @return Path\FilePathGeneratorInterface
	 */
	public function getFilePathGenerator()
	{
		if ($this->filePathGenerator === null) {
			
			$this->filePathGenerator = new Path\DefaultFilePathGenerator(
					new Path\Transformer\DefaultPathTransformer, 
					$this
			);
		}
		
		return $this->filePathGenerator;
	}

	/**
	 * Store file data
	 *
	 * @param File $file
	 * @param string $source
	 */
	public function storeFileData(File $file, $sourceFilePath)
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

		$originFile = clone($file);
		$entityManager->detach($originFile);

		try {
			
			$file->setFileName($fileName);
			
		} catch (\Exception $e) {
			
			throw new Exception\UploadFilterException(self::VALIDATION_EXTENSION_RENAME_MESSAGE_KEY);
		}

		$this->validateFileUpload($file);

		// rename is wrapped into transaction so if something will fail in rename()
		// entity changes will be rolled back
		$entityManager->beginTransaction();
		
		try { 
			// flush will trigger path generator and path transformers
			$entityManager->flush($file);
			
			$this->renameFileInFileSystem($originFile, $file);
			
		} catch (\Exception $e) {	
			$entityManager->rollback();
			throw $e;
		}
		
		$entityManager->commit();
	}

	/**
	 * Actual file rename which is triggered by $this->renameFile();
	 * @param Entity\File $file
	 * @param string $filename new file name
	 */
	public function renameFileInFileSystem(Entity\File $originFile, Entity\File $newFile)
	{
		$originFilePath = $this->getFilesystemPath($originFile);
		
		if ( ! file_exists($originFilePath)) {
			// silently exit to allow to rename physically non existing files
			return false;
		}

		$newPath = $this->getFilesystemPath($newFile);
		
		if (! rename($originFilePath, $newPath)) {
			throw new Exception\RuntimeException('File renaming failed');
		}

		if ($originFile instanceof Entity\Image) {
			
			$sizes = $originFile->getImageSizeCollection();
			
			foreach ($sizes as $size) {
				
				$sizeName = $size->getName();
				
				$newPath = $this->getImagePath($newFile, $sizeName);
				
				$currentPath = $this->getImagePath($originFile, $sizeName);
			
				rename($currentPath, $newPath);
			}
		}
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
		$entityManager->beginTransaction();
		$oldFolder = clone($folder);
		$entityManager->detach($oldFolder);

		try {
			$folder->setFileName($newTitle);

			// validating folder before renaming
			foreach ($this->folderUploadFilters as $filter) {
				$filter->validateFolder($folder);
			}

			$entityManager->flush();

			// rename folder in both file storages
			$this->renameFolderInFileSystem($oldFolder, $folder);
		} catch (\Exception $e) {
			$entityManager->detach($folder);
			$entityManager->rollback();

			throw $e;
		}

		$entityManager->commit();
	}

	/**
	 * Actual folder rename which is triggered by $this->renameFolder();
	 * @param Entity\Folder $folder
	 * @param Entity\Folder $newFolder new folder data
	 * @param string $path
	 */
	public function renameFolderInFileSystem(Entity\Folder $folder, Entity\Folder $newFolder)
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
	 * @param Folder $folder
	 */
	public function createFolder(Folder $folder)
	{
		// validating folder before creation
		foreach ($this->folderUploadFilters as $filter) {
			$filter->validateFolder($folder);
		}

		$this->createBothFoldersInFileSystem($folder);
	}

	/**
	 * Creates the filesystem folder in both storages -- internal and external
	 * @param FileAbstraction $folder
	 */
	private function createBothFoldersInFileSystem(FileAbstraction $folder = null)
	{
		if ($folder instanceof File) {
			$folder = $folder->getParent();
		}

		$this->createFolderInFileSystem($this->getExternalPath(), $folder);
		$this->createFolderInFileSystem($this->getInternalPath(), $folder);
	}

	/**
	 * Actual folder creation function which is triggered by $this->createFolder();
	 * @param string $basePath
	 * @param Folder $folder
	 * @return true or throws Exception\RuntimeException
	 */
	private function createFolderInFileSystem($basePath, Folder $folder = null)
	{
		$destination = '';
		if ( ! is_null($folder)) {
			$destination = $folder->getPath(DIRECTORY_SEPARATOR, true);
		}

		$fullPath = $basePath . $destination;

		if ( ! is_dir($fullPath)) {

			if (file_exists($fullPath)) {
				throw new \RuntimeException('Could not create folder in '
						. $fullPath . ', file exists with the same name');
			}

			if (mkdir($fullPath, $this->folderAccessMode, true)) {
				return true;
			} else {
				throw new \RuntimeException('Could not create folder in ' . $fullPath);
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
	 * @param \Supra\FileStorage\Entity\Image $file
	 * @param integer $width
	 * @param integer $height
	 * @param integer $cropLeft
	 * @param integer $cropTop
	 * @param integer $cropWidth
	 * @param integer $cropHeight
	 * @param integer $quality
	 * @param integer $force
	 * @throws Exception\RuntimeException
	 */
	public function createImageVariant(Entity\Image $file, $width, $height, $cropLeft, $cropTop, $cropWidth, $cropHeight, $quality = 95, $force = false)
	{
		
		if ( ! ($cropLeft || $cropTop || $cropWidth || $cropHeight) || 
				( ! $cropLeft && ! $cropTop && $cropWidth == $width && $cropHeight == $height)) {
				
			$resizedVariantName = $this->createResizedImage($file, $width, $height);
			return $resizedVariantName;
		}
		else {
			
			$resizedVariantName = $this->createResizedImage($file, $width, $height, true);
		}

		$cropLeft = intval($cropLeft);
		$cropTop = intval($cropTop);
		$cropWidth = intval($cropWidth);
		$cropHeight = intval($cropHeight);

		if ( ! (
				($cropLeft >= 0) ||
				($cropLeft < $width) ||
				($cropWidth >= 1) ||
				($cropLeft + $cropWidth <= $width) ||
				($cropTop >= 0) ||
				($cropTop < $height) ||
				($cropHeight >= 1) ||
				($cropTop + $cropHeight <= $height)
				)
		) {
			throw new Exception\RuntimeException('Crop parametrs are invalid');
		}

		$croppedVariantName = $this->getImageSizeNameForCrop($width, $height, $cropLeft, $cropTop, $cropWidth, $cropHeight);

		$variant = $file->findImageSize($croppedVariantName);
		if ($variant !== null) {
			// already exists, nothing to do
			return $variant->getName();
		}
		
		$variant = $file->getImageSize($croppedVariantName);

		$variant->setQuality($quality);
		
		$variant->setTargetWidth($width);
		$variant->setWidth($width);
		
		$variant->setTargetHeight($height);
		$variant->setHeight($height);
		
		$variant->setCropTop($cropTop);
		$variant->setCropLeft($cropLeft);
		$variant->setCropWidth($cropWidth);
		$variant->setCropHeight($cropHeight);

		$resizedVariantFilename = $this->getImagePath($file, $resizedVariantName);
		
		if ( ! file_exists($resizedVariantFilename)) {
			$this->log()->warn("Resized image file {$resizedVariantFilename} is missing in the filesystem");

			return $resizedVariantName;
		}
		
		$cropper = $this->getImageCropper();

		$cropper->setSourceFile($resizedVariantFilename);
		$cropper->setOutputQuality($quality);
		$cropper->setTop($cropTop);
		$cropper->setLeft($cropLeft);
		$cropper->setBottom($cropTop + $cropHeight - 1);
		$cropper->setRight($cropLeft + $cropWidth - 1);

		$variantFileDir = $this->getFilesystemDir($file)
				. self::RESERVED_DIR_SIZE . DIRECTORY_SEPARATOR
				. $variant->getFolderName();

		if ( ! file_exists($variantFileDir)) {
			$mkdirResult = mkdir($variantFileDir, $this->folderAccessMode, true);
			if (empty($mkdirResult)) {
				throw new Exception\RuntimeException(
						'Could not create directory for image variant');
			}
		}

		$croppedVariantFilename = $variantFileDir . DIRECTORY_SEPARATOR . $file->getFileName();
		$cropper->setOutputFile($croppedVariantFilename);
		$cropper->process();

		$entityManager = $this->getDoctrineEntityManager();

		$entityManager->persist($variant);
		$entityManager->flush();

		return $croppedVariantName;
	}

	/**
	 * Create resized version for the image
	 * @param Image $file
	 * @param integer $targetWidth
	 * @param integer $targetHeight
	 * @param boolean $cropped 
	 * @param integer $quality
	 * @param boolean $force
	 * @return string
	 */
	public function createResizedImage(Image $file, $targetWidth, $targetHeight, $cropped = false, $quality = 95, $force = false)
	{
		// validate params
		if ( ! $file instanceof Image) {
			throw new Exception\RuntimeException('Image entity expected');
		}
		if (($targetWidth <= 0) || ($targetHeight <= 0)) {
			throw new Exception\RuntimeException('Dimensions are invalid');
		}

		$sizeName = $this->getImageSizeName($targetWidth, $targetHeight, $cropped);

		if ( ! $this->fileExists($file)) {
			$this->container->getLogger()->warn("Image '{$file->getFileName()}' is missing in the filesystem, tried to resize to {$sizeName}");

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
		$resizer = $this->getImageResizer();

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

		$resizedFilePath = $resizedFileDir . DIRECTORY_SEPARATOR . $file->getRealFileName();
		$resizer->setOutputFile($resizedFilePath);
		$resizer->process();

		$entityManager = $this->getDoctrineEntityManager();

		$entityManager->persist($size);
		$entityManager->flush();

		return $sizeName;
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
	public function createCroppedImageVariant(Entity\ImageSize $sourceImageSize, $targetWidth, $targetHeight, $cropped = false, $quality = 95, $force = false)
	{
		// validate params
		if ( ! $sourceImageSize instanceof Entity\ImageSize) {
			throw new Exception\RuntimeException('ImageSize entity expected');
		}
		if (($targetWidth <= 0) || ($targetHeight <= 0)) {
			throw new Exception\RuntimeException('Dimensions are invalid');
		}
		
		if ( ! $sourceImageSize->isCropped()) {
			//throw new Exception\RuntimeException('Cannot resize non-cropped images with this metod');
		}
		
		$image = $sourceImageSize->getMaster();

		$sizeName = $this->getImageSizeNameForResizedCrop($targetWidth, $targetHeight, $sourceImageSize->getTargetWidth(), $sourceImageSize->getTargetHeight(), $sourceImageSize->getCropLeft(), $sourceImageSize->getCropTop(), $sourceImageSize->getCropWidth(), $sourceImageSize->getCropHeight(), $cropped);
			
		if ( ! $this->fileExists($image)) {
			$this->log()->warn("Image '{$image->getFileName()}' is missing in the filesystem, tried to resize cropped variant to {$sizeName}");

			return $sizeName;
		}
		
		$size = $image->getImageSize($sizeName);

		if (($size->getTargetHeight() == $targetHeight)
				&& ($size->getTargetWidth() == $targetWidth)
				&& ($size->getQuality() == $quality)
				&& ($size->getCropMode() == $cropped)
				&& empty($force)
		) {
			// nothing to update
			return $sizeName;
		}
		
		$size->setCropTop($sourceImageSize->getCropTop());
		$size->setCropLeft($sourceImageSize->getCropLeft());
		$size->setCropWidth($sourceImageSize->getCropWidth());
		$size->setCropHeight($sourceImageSize->getCropHeight());
		
		$size->setCropSourceWidth($sourceImageSize->getWidth());
		$size->setCropSourceHeight($sourceImageSize->getHeight());
	
		$size->setQuality($quality);
		$size->setCropMode($cropped);
		$size->setTargetWidth($targetWidth);
		$size->setTargetHeight($targetHeight);

		$variantFileName = $this->getImagePath($image, $sourceImageSize->getName());

		if ( ! file_exists($variantFileName)) {
			$this->log()->warn("Image cropped variant file {$variantFileName} is missing in the filesystem");

			return $sizeName;
		}
		
		$resizer = $this->getImageResizer();
		
		$resizer->setSourceFile($variantFileName)
				->setOutputQuality($quality)
				->setTargetWidth($targetWidth)
				->setTargetHeight($targetHeight)
				->setCropMode($cropped);

		$expectedSize = $resizer->getExpectedSize();
		$size->setWidth($expectedSize['width']);
		$size->setHeight($expectedSize['height']);

		$resizedFileDir = $this->getFilesystemDir($image)
				. self::RESERVED_DIR_SIZE . DIRECTORY_SEPARATOR
				. $size->getFolderName();

		if ( ! file_exists($resizedFileDir)) {
			$mkdirResult = mkdir($resizedFileDir, $this->folderAccessMode, true);
			if (empty($mkdirResult)) {
				throw new Exception\RuntimeException(
						'Could not create directory for resized image');
			}
		}

		$resizedFilePath = $resizedFileDir . DIRECTORY_SEPARATOR . $image->getFileName();
		$resizer->setOutputFile($resizedFilePath);
		$resizer->process();

		$entityManager = $this->getDoctrineEntityManager();

		$entityManager->persist($size);
		
		$size->setCropSourceWidth($sourceImageSize->getWidth());
		$size->setCropSourceHeight($sourceImageSize->getHeight());
		
		$entityManager->flush();

		return $sizeName;
	}
	
	
	public function getImageSizeNameForResizedCrop($targetWidth, $targetHeight, $sourceWidth, $sourceHeight, $cropLeft, $cropTop, $cropWidth, $cropHeight)
	{
		$sizeNameParts = array($targetWidth, 'x', $targetHeight, 'c', $sourceWidth, 'x', $sourceHeight);

		if ($cropLeft || $cropTop || $cropWidth || $cropHeight) {
			$sizeNameParts[] = 't';
			$sizeNameParts[] = intval($cropTop);
			$sizeNameParts[] = 'l';
			$sizeNameParts[] = intval($cropTop);
			$sizeNameParts[] = 'w';
			$sizeNameParts[] = intval($cropWidth);
			$sizeNameParts[] = 'h';
			$sizeNameParts[] = intval($cropHeight);
		}

		return join('', $sizeNameParts);
	}

	/**
	 * @param integer $width
	 * @param integer $height
	 * @param integer $cropLeft
	 * @param integer $cropTop
	 * @param integer $cropWidth
	 * @param integer $cropHeight
	 * @return string
	 */
	public function getImageSizeNameForCrop($width, $height, $cropLeft, $cropTop, $cropWidth, $cropHeight)
	{
		$sizeNameParts = array($width, 'x', $height);

		if ($cropLeft || $cropTop || $cropWidth || $cropHeight) {
			$sizeNameParts[] = 't';
			$sizeNameParts[] = intval($cropTop);
			$sizeNameParts[] = 'l';
			$sizeNameParts[] = intval($cropLeft);
			$sizeNameParts[] = 'w';
			$sizeNameParts[] = intval($cropWidth);
			$sizeNameParts[] = 'h';
			$sizeNameParts[] = intval($cropHeight);
		}

		return join('', $sizeNameParts);
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
	 * @param Image $file
	 */
	protected function recreateImageSizes(Image $file)
	{
		if ( ! $file instanceof Image) {
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
		$rotator = $this->getImageRotator();
		$rotator->setSourceFile($filename)
				->setOutputFile($filename)
				->setOutputQuality($quality)
				->setRotationCount($rotationCount);
		$rotator->rotate();

		if ((abs($rotationCount) % 2) == 1) {
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
		$cropper = $this->getImageCropper();
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
	 * Is current image format supported by image processor
	 * @param string $filename Path to file
	 */
	public function isSupportedImageFormat($filename)
	{
		$info = new ImageInfo($filename);
		if ($info->hasError()) {
			\Log::error($info->getError());
			return false;
		}

		if ( ! $this->isMimeTypeImage($info->getMime())) {
			return false;
		}

		return ImageProcessor\ImageProcessor::isSupportedImageType($info->getType());
	}

	/**
	 * Get full file path or its directory (with trailing slash)
	 * @param File $file
	 * @param boolean $includeFilename
	 * @param integer $forcePath Forces external or internal path. Use FILE_INFO_EXTERNAL and FILE_INFO_INTERNAL constants
	 * @return string
	 */
	public function getFilesystemPath(FileAbstraction $file, $includeFilename = true, $forcePath = null)
	{
		if ( ! $file instanceof FileAbstraction) {
			throw new Exception\RuntimeException('File or folder entity expected');
		}

		$path = $this->getInternalPath();

		if ($forcePath != self::FILE_INFO_INTERNAL) {
			if ($file->isPublic() || $forcePath == self::FILE_INFO_EXTERNAL) {
				$path = $this->getExternalPath();
			}
		}

		$path .= $file->getPath(DIRECTORY_SEPARATOR, $includeFilename);
		
		if ( ! $includeFilename) {
			$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
	
		return $path;
	}

	/**
	 * Get file directory (with trailing slash)
	 * @param File $file
	 * @return string
	 */
	public function getFilesystemDir(File $file)
	{
		return $this->getFilesystemPath($file, false);
	}

	/**
	 * Get full file path for image size
	 * @param Image $file
	 * @param string $sizeName
	 * @return string
	 */
	public function getImagePath(Image $file, $sizeName = null)
	{
		if ( ! $file instanceof Image) {
			throw new Exception\RuntimeException('Image entity expected');
		}

		$path = $this->getFilesystemDir($file);
		$size = $file->findImageSize($sizeName);
				
		if ($size instanceof ImageSize) {
			$path .= self::RESERVED_DIR_SIZE 
					. DIRECTORY_SEPARATOR
					. $size->getFolderName() 
					. DIRECTORY_SEPARATOR;
		}

		$path .= $file->getRealFileName();

		return $path;
	}

	/**
	 * Get web (external) path for file
	 * @param File $file
	 * @param ImageSize $size | string $size
	 * @return string 
	 */
	public function getWebPath(File $file, $size = null)
	{
		if ( ! $file instanceof File) {
			throw new Exception\RuntimeException('File or folder entity expected');
		}

		if ($file instanceof Image && isset($size)) {
			if ( ! $size instanceof ImageSize) {
				$size = $file->findImageSize($size);
			}
		} else {
			$size = null;
		}

		if ($file->isPublic()) {

			$path = $file->getPathEntity()->getWebPath();

			$pathParts = explode('/', $path);
			
			$fileName = array_pop($pathParts);

			// Get file storage url base in webroot
			$path = '/' . trim(implode('/', $pathParts), '/\\') . '/';
			if ($size instanceof ImageSize) {
				$path .= self::RESERVED_DIR_SIZE . '/'
						. rawurlencode($size->getFolderName()) . '/';
			}

			return $path . $fileName;
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
	public function replaceFile(File $fileEntity, UploadedFile $file)
	{
		$entityManager = $this->getDoctrineEntityManager();
		$oldFileIsImage = $fileEntity instanceof Image;
		$newFileIsImage = $this->isMimeTypeImage($file->getMimeType());

		if ($oldFileIsImage !== $newFileIsImage) {
			throw new Exception\UploadFilterException(self::VALIDATION_IMAGE_TO_FILE_REPLACE_MESSAGE_KEY, 'New file should be image too');
		}

		$originalFile = clone($fileEntity);
		$entityManager->detach($originalFile);

		// setting new data
		// The name is not changed by Bug #6756
//		$fileEntity->setFileName($file['name']);
		$fileEntity->setSize($file->getSize());
		$fileEntity->setMimeType($file->getMimeType());

		// This must be call before removing the old file
		$this->validateFileUpload($fileEntity, $file->getPathname());

		$this->removeFileInFileSystem($originalFile);

		$this->storeFileData($fileEntity, $file->getPathname());

		// additional jobs for images
		if ($fileEntity instanceof Image) {
			
			$imageProcessor  = $this->getImageResizer();
			// store original size
			$imageInfo = $imageProcessor->getImageInfo($fileEntity);
			$fileEntity->setWidth($imageInfo->getWidth());
			$fileEntity->setHeight($imageInfo->getHeight());
			// reprocess sizes
			$this->recreateImageSizes($fileEntity);
		}

		$fileEntity->setModificationTime();

		$entityManager->flush();
	}

	/**
	 * Remove file in file system
	 * @param File $file
	 */
	private function removeFileInFileSystem(File $file)
	{
		$filePath = $this->getFilesystemPath($file);

		if (file_exists($filePath)) {
			$result = unlink($filePath);

			if ( ! $result) {
				throw new Exception\RuntimeException("Could not delete '$filePath' from file storage");
			}
		}

		// remove sizes if object is image
		if ($file instanceof Image) {
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

	protected function fireFileEvent($type, $event)
	{
		$this->container->getEventDispatcher()->dispatch($type, $event);
	}

	/**
	 * Remove file or folder from database and system
	 * @param FileAbstraction $entity
	 */
	public function remove(FileAbstraction $entity)
	{
		$eventArgs = new FileEvent();
		$eventArgs->setFile($entity);
		$this->fireFileEvent(FileEvent::FILE_EVENT_PRE_DELETE, $eventArgs);

		if ($entity instanceof Folder) {
			$hasChildren = $entity->hasChildren();
			if ($hasChildren) {
				throw new Exception\NotEmptyException('You can remove only empty folders');
			}
			$this->removeFolder($entity);
		} elseif ($entity instanceof File) {
			$this->removeFile($entity);
		} else {
			throw new Exception\LogicException('Not recognized file type passed: ' . get_class($entity));
		}
	}

	/**
	 * Remove folder from database and file system
	 * @param Folder $folder
	 */
	private function removeFolder(Folder $folder)
	{
		$this->removeFolderInFileSystem($folder);

		$entityManager = $this->getDoctrineEntityManager();
		$entityManager->remove($folder);
		$entityManager->flush();
	}

	/**
	 * Remove folder in file system
	 * @param Folder $folder
	 */
	private function removeFolderInFileSystem(Folder $folder)
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
	 * @param FileAbstraction $file
	 * @param string $locale
	 * @return array
	 */
	public function getFileInfo(FileAbstraction $file, $locale = null)
	{
		$info = $file->getInfo($locale);

		if ($file instanceof File) {
			$filePath = $this->getWebPath($file);
			$info['file_web_path'] = $filePath;

			if ($file instanceof Image) {

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
	 * @param File $file
	 * @return boolean
	 */
	public function fileExists(FileAbstraction $file)
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
	
	/**
	 * @return ImageProcessor\Adapter\ImageProcessorAdapterInterface
	 */
	public function getImageProcessorAdapter()
	{
		if (is_null($this->adapter)) {
			$this->autoloadImageProcessorAdapter();
		}
		
		return $this->adapter;
	}
	
	/**
	 * @param ImageProcessor\Adapter\ImageProcessorAdapterInterface $adapter
	 */
	public function setImageProcessorAdapter(ImageProcessor\Adapter\ImageProcessorAdapterInterface $adapter)
	{
		$this->adapter = $adapter;
	}
		
	/**
	 *
	 */
	private function autoloadImageProcessorAdapter()
	{
		if (ImageProcessor\Adapter\ImageMagickAdapter::isAvailable()) {
			
			$this->adapter = new ImageProcessor\Adapter\ImageMagickAdapter();
		}
		else if (ImageProcessor\Adapter\Gd2Adapter::isAvailable()) {
			
			$this->adapter = new ImageProcessor\Adapter\Gd2Adapter();
		}
		else {
			throw new Exception\RuntimeException('Failed to autoload image processor adapter, 
				please specify one by yourself using FileStorage configuration');
		}
	}

	/**
	 * @return ImageProcessor\ImageResizer
	 */
	public function getImageResizer()
	{
		return new ImageProcessor\ImageResizer($this->getImageProcessorAdapter());
	}
	
	/**
	 * @return ImageProcessor\ImageCropper
	 */
	public function getImageCropper()
	{
		return new ImageProcessor\ImageCropper($this->getImageProcessorAdapter());
	}
	
	/**
	 * @return ImageProcessor\ImageRotator
	 */
	public function getImageRotator()
	{
		return new ImageProcessor\ImageRotator($this->getImageProcessorAdapter());
	}
    
	/**
	 * @param string $entityClassname
	 * @return integer
	 */
	public function getFileSizeTotalForEntity($entityClassname)
	{
		return (int) $this->getDoctrineEntityManager()
				->createQuery("SELECT SUM(f.fileSize) as total FROM {$entityClassname} f")
				->getSingleScalarResult();
	}
	
	/**
	 * @param PropertyConfiguration $configuration
	 */
	public function addCustomPropertyConfiguration(PropertyConfiguration $configuration)
	{
		$this->customPropertyConfigurations[$configuration->name] = $configuration;
	}
	
	/**
	 * @return array
	 */
	public function getCustomPropertyConfigurations()
	{
		return $this->customPropertyConfigurations;
	}
	
	/**
	 * @param Entity\Abstraction\File $file
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getFileCustomPropertyValue(Entity\Abstraction\File $file, $propertyName)
	{
		if ( ! isset($this->customPropertyConfigurations[$propertyName])) {
			throw new \RuntimeConfiguration("Property '{$propertyName}' is not configured");
		}
		
		$property = $file->getCustomProperties()
				->get($propertyName);
		/* @var $property Supra\FileStorage\Entity\FileProperty */
		
		if ($property instanceof Entity\FileProperty) {
			// @TODO: pass through editable? Filter?
			return $property->getValue();
		}
		
		$configuration = $this->customPropertyConfigurations[$propertyName];
		
		return $configuration->default;
	}
	
	/**
	 * @param Entity\Abstraction\File $file
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getFileCustomProperty(Entity\Abstraction\File $file, $propertyName)
	{
		if ( ! isset($this->customPropertyConfigurations[$propertyName])) {
			throw new \RuntimeConfiguration("Property '{$propertyName}' is not configured");
		}
		
		$property = $file->getCustomProperties()
				->get($propertyName);
		/* @var $property Supra\FileStorage\Entity\FileProperty */
		
		if ( ! $property instanceof Entity\FileProperty) {
			
			$property = new Entity\FileProperty($propertyName, $file);
			
			$this->getDoctrineEntityManager()
					->persist($property);
		}
		
		return $property;
				
	}
}
