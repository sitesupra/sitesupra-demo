<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Entity\Abstraction\File as FileAbstraction;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\Entity\Folder;
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\ImageSize;
use Supra\Package\Cms\Entity\SlashFolder;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\FileStorage\Exception\DuplicateFileNameException;
use Supra\Package\Cms\FileStorage\Exception\InsufficientSystemResources;
use Supra\Package\Cms\FileStorage\Exception\UploadFilterException;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\Repository\FileNestedSetRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class MediaLibraryController extends Controller
{
	protected $application = 'media-library';

	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	const CHECK_FULL = 'full';
	const CHECK_PARTIAL = 'partial';
	const CHECK_NONE = 'none';

	const DUPLICATE_NAME_PATTERN = '%s (%d).%s';

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}

	/**
	 * File upload action
	 */
	public function uploadAction(Request $request)
	{
		$postRequest = $request->request;
		$files = $request->files;

		if (!$files->has('file') || !$files->get('file') instanceof UploadedFile || !$files->get('file')->isValid()) {
			$message = 'Error uploading the file';

			throw new CmsException(null, $message);
		}

		$file = $files->get('file');
		/* @var $file UploadedFile */

		$em = $this->container->getDoctrine()->getManager();

		$em->beginTransaction();

		$repository = $em->getRepository(FileAbstraction::CN());
		/* @var $repository FileNestedSetRepository */

		$repository->getNestedSetRepository()->lock();

		// Permission check
		$uploadPermissionCheckFolder = null;

		if ($postRequest->get('folder')) {
			$uploadPermissionCheckFolder = $this->getFolder('folder');
		} else {
			$uploadPermissionCheckFolder = new SlashFolder();
		}

		$this->checkActionPermission($uploadPermissionCheckFolder, FileAbstraction::PERMISSION_UPLOAD_NAME);

		try {
			// getting the folder to upload in
			$folder = null;
			if ($postRequest->get('folder')) {
				$folder = $this->getFolder('folder');
			}

			// Will return the top folder created/found from folderPath string
			$firstSubFolder = null;

			// Create/get folder by path provided
			$folderPath = $postRequest->get('folderPath', '');

			$folderPath = trim(str_replace('\\', '/', $folderPath), '/');

			if ( ! empty($folderPath)) {
				$folderPathParts = explode('/', $folderPath);
				foreach ($folderPathParts as $part) {

					$folderFound = false;
					$children = null;

					if ($folder instanceof Folder) {
						$children = $folder->getChildren();
					} elseif (is_null($folder)) {
						$children = $repository->getRootNodes();
					} else {
						throw new \LogicException("Not supported folder type: " . gettype($folder) . ', class: ' . get_class($folder));
					}

					foreach ($children as $child) {
						if ($child instanceof Folder) {
							$_name = $child->getTitle();
							if (strcasecmp($_name, $part) === 0) {
								$folderFound = $child;
								break;
							}
						}
					}

					if ($folderFound) {
						$folder = $folderFound;
					} else {
						$folder = $this->createFolder($part, $folder);
					}

					if (empty($firstSubFolder)) {
						$firstSubFolder = $folder;
					}
				}
			}

			// checking for replace action
			if ($postRequest->has('file_id')) {
				$fileToReplace = $this->getFile('file_id');
				$this->getFileStorage()->replaceFile($fileToReplace, $file);

				// close transaction and unlock the nested set
				$em->commit();
				$repository->getNestedSetRepository()->unlock();

				return new SupraJsonResponse($this->imageAndFileOutput($fileToReplace));
			}

			$fileEntity = null;

			if ($this->getFileStorage()->isSupportedImageFormat($file->getPathname())) {
				$fileEntity = new Image();
			} else {
				$fileEntity = new File();
			}
			$em->persist($fileEntity);

			$fileEntity->setFileName($file->getClientOriginalName());
			$fileEntity->setSize($file->getSize());
			$fileEntity->setMimeType($file->getMimeType());

			// additional jobs for images
			if ($fileEntity instanceof Image) {
				// store original size
				$imageProcessor = $this->getFileStorage()->getImageResizer();
				$imageInfo = $imageProcessor->getImageInfo($file->getPathname());
				$fileEntity->setWidth($imageInfo->getWidth());
				$fileEntity->setHeight($imageInfo->getHeight());
			}

			if ( ! empty($folder)) {
				// get parent folder private/public status
				$publicStatus = $folder->isPublic();
				$fileEntity->setPublic($publicStatus);

				// Flush before nested set UPDATE
				$em->flush();

				$folder->addChild($fileEntity);
			}

			// when "force" set to true, then we need to ignore duplicate
			// filename exception, so postfix will be added to filename
			if ($fileEntity instanceof File) {
				if ($postRequest->has('force') && $postRequest->filter('force', null, false, FILTER_VALIDATE_BOOLEAN)) {
					try {
						$this->getFileStorage()->validateFileUpload($fileEntity, $file['tmp_name']);
					} catch (DuplicateFileNameException $e) {

						$siblings = $fileEntity->getSiblings();

						$existingNames = array();
						foreach($siblings as $siblingEntity) {
							if ( ! $siblingEntity->equals($fileEntity)) {
								$existingNames[] = $siblingEntity->getFileName();
							}
						}

						$extension = $fileEntity->getExtension();
						$fileNamePart = $fileEntity->getFileNameWithoutExtension();

						$possibleName = null;
						// assume that 1000 iterations is enough, to create unique name
						// if not, well... duplicate file name exception will be thrown
						for ($i = 1; $i < 1000; $i++) {
							$possibleName = sprintf(self::DUPLICATE_NAME_PATTERN, $fileNamePart, $i, $extension);

							if ( ! in_array($possibleName, $existingNames)) {
								$fileEntity->setFileName($possibleName);
								break;
							}
						}
					}
				}
			}

			// when it is not enough available memory to complete Image resize/crop
			// file will be uploaded as simple File entity
			if ($fileEntity instanceof Image) {
				try {
					$this->getFileStorage()->validateFileUpload($fileEntity, $file->getPathname());
				} catch (InsufficientSystemResources $e) {

					// Removing image
					$em->remove($fileEntity);
					$em->flush();

					$fileEntity = new File();

					$em->persist($fileEntity);

					$fileEntity->setFileName($file->getClientOriginalName());
					$fileEntity->setSize($file->getSize());
					$fileEntity->setMimeType($file->getType());

					if ( ! is_null($folder)) {
						$publicStatus = $folder->isPublic();
						$fileEntity->setPublic($publicStatus);

						// Flush before nested set UPDATE
						$em->flush();

						$folder->addChild($fileEntity);
					}

					$message = "Amount of memory required for image [{$file['name']}] resizing exceeds available, it will be uploaded as a document";

					$responseWarning = $message;
				}
			}

			$em->flush();

			// trying to upload file
			$this->getFileStorage()->storeFileData($fileEntity, $file->getPathname());
		} catch (\Exception $e) {

			try {
				// close transaction and unlock the nested set
				$em->flush();
				$em->rollback();
				$repository->getNestedSetRepository()->unlock();
			} catch (\Exception $e) {
				$this->container->getLogger()->error("Failure on rollback/unlock: ".$e->__toString());
			}

			throw new CmsException(null, $e->getMessage(), $e);
		}

		// close transaction and unlock the nested set
		$em->commit();
		$repository->getNestedSetRepository()->unlock();

		// generating output
		$output = $this->imageAndFileOutput($fileEntity);

		if ( ! empty($firstSubFolder)) {
			$firstSubFolderOutput = $this->entityToArray($firstSubFolder);
			$output['folder'] = $firstSubFolderOutput;
		}

		$response = new SupraJsonResponse();

		$response->setData($output);

		if (isset($responseWarning)) {
			$response->setWarningMessage($responseWarning);
		}

		return $response;
	}

	/**
	 * Creates new folders, despite it's name
	 */
	public function insertAction(Request $request)
	{
		$manager = $this->container->getDoctrine()->getManager();
		$repository = $manager->getRepository(FileAbstraction::CN());
		/* @var $repository FileNestedSetRepository */
		$repository->getNestedSetRepository()->lock();
		$manager->beginTransaction();

		try {
			if (!$request->request->has('filename')) {
				throw new CmsException(null, 'Folder title was not sent');
			}

			$dirName = $request->request->get('filename');
			$parentFolder = null;

			// Adding child folder if parent exists
			if ($request->request->has('parent')) {
				$parentFolder = $this->getFolder('parent');
			}

			$dir = $this->createFolder($dirName, $parentFolder);

			$manager->commit();
		} catch (\Exception $e) {
			$manager->rollback();
			$key = $e instanceof UploadFilterException ? $e->getMessageKey() : null;
			throw new CmsException($key, $e->getMessage(), $e);
		}

		$insertedId = $dir->getId();
		return new SupraJsonResponse($insertedId);
	}

	/**
	 * Deletes file or folder
	 */
	public function deleteAction()
	{
		$repository = $this->container->getDoctrine()->getManager()->getRepository(FileAbstraction::CN());
		/* @var $repository FileNestedSetRepository */
		$repository->getNestedSetRepository()->lock();

		$file = $this->getEntity();

		$this->checkActionPermission($file, FileAbstraction::PERMISSION_DELETE_NAME);

		if (is_null($file)) {
			throw new CmsException(null, 'File doesn\'t exist anymore');
		}

		// try to delete
		try {
			if ($file->hasChildren()) {
				$this->getConfirmation('Are You sure?');

				$this->removeFilesRecursively($file);
			} else {
				$this->removeSingleFile($file);
			}
		} catch (NotEmptyException $e) {
			// Should not happen
			throw new CmsException(null, "Cannot delete not empty folders");
		}

		return new SupraJsonResponse(null);
	}

	/**
	 * Lists filesystem objects
	 */
	public function listAction(Request $request)
	{
		$rootNodes = array();

		$repo = $this->container->getDoctrine()
			->getManager()->getRepository('Supra\Package\Cms\Entity\Abstraction\File');

		$output = array();

		// if parent dir is set then we set folder as rootNode
		if ($request->query->get('id')) {
			$node = $this->getFolder('id');
			$rootNodes = $node->getChildren();
		} else {
			$rootNodes = $repo->getRootNodes();
		}

		foreach ($rootNodes as $rootNode) {
			/* @var $rootNode Entity\Abstraction\File */

			if ($request->query->has('type')) {

				$itemType = $this->getEntityType($rootNode);
				$requestedType = $request->query->get('type');

				if ( ! (
					($itemType == $requestedType) ||
					($itemType == Folder::TYPE_ID)
				)) {
					continue;
				}
			}

			$item = $this->entityToArray($rootNode);

			if ($rootNode instanceof File) {

				$extension = mb_strtolower($rootNode->getExtension());

				$knownExtensions = $this->container->getParameter('cms.media_library_known_file_extensions');
				if (in_array($extension, $knownExtensions)) {
					$item['known_extension'] = $extension;
				}

				$checkExistance = $this->container->getParameter('cms.media_library_check_file_existence');
				if ($checkExistance == self::CHECK_FULL) {
					$item['broken'] = ( ! $this->isAvailable($rootNode));
				}
			}

			// Get thumbnail
			if ($rootNode instanceof Image) {
				// create preview
				// TODO: hardcoded 30x30
				try {
					if ($this->fileStorage->fileExists($rootNode)) {
						$sizeName = $this->fileStorage->createResizedImage($rootNode, 30, 30, true);
						if ($rootNode->isPublic()) {
							$item['thumbnail'] = $this->fileStorage->getWebPath($rootNode, $sizeName);
						} else {
							$item['thumbnail'] = $this->getPrivateImageWebPath($rootNode, $sizeName);
						}
					}
				} catch (\Exception $e) {
					$item['broken'] = true;
				}
			}

			$output[] = $item;
		}

		return new SupraJsonResponse(array(
			'totalRecords' => count($output),
			'records' => $output,
		));
	}



	/**
	 * File info array
	 * @param File $file
	 * @return array
	 */
	private function imageAndFileOutput(File $file, $localeId = null)
	{
		$request = $this->container->getRequest();
		$postInput = $request->isMethod('POST') ? $request->request : $request->query;
		$requestedSizes = $postInput->get('sizes', array());
		$requestedSizeNames = array();
		$isBroken = false;
		$thumbSize = null;
		$previewSize = null;

		try {
			if ($file instanceof Image && $this->getFileStorage()->fileExists($file)) {

				$thumbSize = $this->getFileStorage()->createResizedImage($file, 30, 30, true);
				$previewSize = $this->getFileStorage()->createResizedImage($file, 200, 200);

				foreach ($requestedSizes as $size) {
					$width = filter_var($size['width'], FILTER_VALIDATE_INT);
					$height = filter_var($size['height'], FILTER_VALIDATE_INT);
					$crop = filter_var($size['crop'], FILTER_VALIDATE_BOOLEAN);

					$requestedSizeNames[] = $this->getFileStorage()->createResizedImage($file, $width, $height, $crop);
				}
			}
		} catch (\Exception $e) {
			$isBroken = true;
		}

		$output = $this->getFileStorage()->getFileInfo($file, $localeId);

		// Return only requested sizes
		if ( ! empty($requestedSizeNames)) {
			foreach ($output['sizes'] as $sizeName => $size) {

				if ($sizeName == 'original') {
					continue;
				}

				if ( ! in_array($sizeName, $requestedSizeNames, true)) {
					unset($output['sizes'][$sizeName]);
				}
			}
		}

		// Create thumbnail&preview
		try {
			if ($file instanceof Image && $this->getFileStorage()->fileExists($file)) {
				if ($file->isPublic()) {
					$output['preview'] = $this->getFileStorage()->getWebPath($file, $previewSize);
					$output['thumbnail'] = $this->getFileStorage()->getWebPath($file, $thumbSize);
				} else {
					$output['thumbnail'] = $this->getPrivateImageWebPath($file, $thumbSize);
					$output['file_web_path'] = $output['preview'] = $this->getPrivateImageWebPath($file);

					if ( ! empty($output['sizes'])) {
						foreach ($output['sizes'] as $sizeName => &$size) {
							$sizePath = null;

							if ($sizeName == 'original') {
								$sizePath = $output['file_web_path'];
							} else {
								$sizePath = $this->getPrivateImageWebPath($file, $sizeName);
							}

							$size['external_path'] = $sizePath;
						}
					}
				}
			}
		} catch (\Exception $e) {
			$output['broken'] = true;
			return $output;
		}

		$extension = mb_strtolower($file->getExtension());
		$knownExtensions = $this->container->getParameter('cms.media_library_known_file_extensions');
		if (in_array($extension, $knownExtensions)) {
			$output['known_extension'] = $extension;
		}

		$checkExistance = $this->container->getParameter('cms.media_library_check_file_existence');

		if ($isBroken) {
			$output['broken'] = true;
		} elseif ($checkExistance == self::CHECK_FULL
			|| $checkExistance == self::CHECK_PARTIAL) {

			$output['broken'] = ( ! $this->isAvailable($file));
		}

		$output['timestamp'] = $file->getModificationTime()->getTimestamp();

		// Custom Properties
		$propertyConfigurations = $this->getFileStorage()->getCustomPropertyConfigurations();
		$propertyData = array();
		foreach ($propertyConfigurations as $configuration) {
			/* @var $configuration PropertyConfiguration */
			$propertyName = $configuration->name;
			$propertyData[$propertyName] = $this->getFileStorage()->getFileCustomPropertyValue($file, $propertyName);
		}

		$output['metaData'] = $propertyData;

		return $output;
	}

	/**
	 * Check whether $file exists and is readable
	 * @param File $file
	 * @return boolean
	 */
	private function isAvailable(File $file)
	{
		$filePath = $this->getFileStorage()
			->getFilesystemPath($file);

		if (is_readable($filePath)) {
			return true;
		}

		return false;
	}

	protected function getPrivateImageWebPath(Image $image, $sizeName = null)
	{
		return 'THIS IS A STUB';
		$path = '/' . SUPRA_CMS_URL . '/media-library/download/' . rawurlencode($image->getFileName());
		$query = array(
			'inline' => 'inline',
			'id' => $image->getId(),
		);

		if ( ! is_null($sizeName)) {

			$imageSize = $image->findImageSize($sizeName);

			if ($imageSize instanceof Entity\ImageSize) {
				$query['size'] = $imageSize->getFolderName();
			}
		}

		$queryOutput = http_build_query($query);

		return $path . '?' . $queryOutput . '&';
	}

	/**
	 * @param FileAbstraction $file
	 */
	protected function removeSingleFile(FileAbstraction $file)
	{
		if ($file instanceof Image) {
			$em = $this->getFileStorage()->getDoctrineEntityManager();
			$imageSizeCn = ImageSize::CN();
			$em->createQuery("DELETE FROM $imageSizeCn s WHERE s.master = :master")
				->setParameter('master', $file->getId())
				->execute();
		}

		$this->getFileStorage()->remove($file);
	}

	/**
	 * @param File $node
	 * @return array
	 */
	protected function entityToArray($node)
	{
		$item = array();

		$item['id'] = $node->getId();
		$item['filename'] = $node->getFileName();
		$item['type'] = $this->getEntityType($node);
		$item['children_count'] = $node->getNumberChildren();
		$item['private'] = ! $node->isPublic();
		$item['timestamp'] = $node->getModificationTime()->getTimestamp();

		return $item;
	}

	/**
	 * @param string $dirName
	 * @param Folder $parentFolder
	 * @return \Supra\Package\Cms\Entity\Folder
	 */
	private function createFolder($dirName, $parentFolder = null)
	{
		$folder = new Folder();
		$manager = $this->container->getDoctrine()->getManager();
		$manager->persist($folder);

		$dirName = trim($dirName);

		if (empty($dirName)) {
			throw new CmsException(null, "Folder name shouldn't be empty");
		}

		$folder->setFileName($dirName);

		// Adding child folder if parent exists
		if (!empty($parentFolder)) {
			// get parent folder private/public status
			$publicStatus = $parentFolder->isPublic();
			$folder->setPublic($publicStatus);

			// Flush before nested set UPDATE
			$manager->flush();

			$parentFolder->addChild($folder);
		}

		$manager->flush();

		// trying to create folder
		$this->getFileStorage()->createFolder($folder);

		return $folder;
	}

	/**
	 * FileStorage getter
	 *
	 * @return FileStorage
	 */
	protected function getFileStorage()
	{
		return $this->container['cms.file_storage'];
	}

	/**
	 * Get internal file entity type constant
	 * @return int
	 */
	protected function getEntityType(FileAbstraction $entity)
	{
		$type = null;

		if ($entity instanceof Folder) {
			$type = self::TYPE_FOLDER;
		} elseif ($entity instanceof Image) {
			$type = self::TYPE_IMAGE;
		} elseif ($entity instanceof File) {
			$type = self::TYPE_FILE;
		}

		return $type;
	}

	/**
	 * @return File
	 */
	protected function getRequestedEntity($key, $className)
	{
		$request = $this->container->getRequest();

		$value = $request->get($key);

		if (!$value) {
			throw new CmsException('medialibrary.validation_error.file_id_not_provided');
		}

		$file = $this->container->getDoctrine()->getManager()->find($className, $value);

		if (is_null($file)) {
			throw new CmsException('medialibrary.validation_error.file_not_exists');
		}

		return $file;
	}

	/**
	 * @return File
	 */
	protected function getEntity($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\Package\Cms\Entity\Abstraction\File');

		return $file;
	}

	/**
	 * @return File
	 */
	protected function getFile($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\Package\Cms\Entity\File');

		return $file;
	}

	/**
	 * @return Folder
	 */
	protected function getFolder($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\Package\Cms\Entity\Folder');

		return $file;
	}

	/**
	 * @return Image
	 */
	protected function getImage($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\Package\Cms\Entity\Image');

		return $file;
	}
}
