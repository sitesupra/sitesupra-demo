<?php

namespace Supra\Package\Cms\Controller;

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
use Supra\Package\Cms\FileStorage\Exception\NotEmptyException;
use Supra\Package\Cms\FileStorage\Exception\UploadFilterException;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\Repository\FileNestedSetRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class MediaLibraryController extends AbstractCmsController
{
	protected $application = 'media-library';

	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	const CHECK_FULL = 'full';
	const CHECK_PARTIAL = 'partial';
	const CHECK_NONE = 'none';

	const DUPLICATE_NAME_PATTERN = '%s (%d).%s';

	const MAX_FILE_BASENAME_LENGTH = 100;

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}

	/**
	 * Used for folder or file renaming
	 */
	public function saveAction(Request $request)
	{
		$file = $this->getEntity();

		// set private
		if ($request->request->has('private')) {
			$private = $request->request->get('private');

			if ($private == 0) {
				$this->getFileStorage()->setPublic($file);
			}

			if ($private == 1) {
				$this->getFileStorage()->setPrivate($file);
			}

			$this->container->getDoctrine()->getManager()->flush();

			return new SupraJsonResponse();
		}

		// renaming
		if ($request->request->has('filename')) {

			$fileName = $request->request->get('filename');

			if (trim($fileName) == '') {
				throw new CmsException(null, 'Empty filename not allowed');
			}

			$originalFileInfo = pathinfo($file->getFileName());

			$newFileInfo = pathinfo($fileName);

			if (mb_strlen($newFileInfo['basename'], 'utf-8') > self::MAX_FILE_BASENAME_LENGTH) {

				if ($file instanceof Folder) {
					throw new CmsException(null, 'Folder name is too long! Maximum length is ' . self::MAX_FILE_BASENAME_LENGTH . ' characters!');
				} else {
					throw new CmsException(null, 'File name is too long! Maximum length is ' . self::MAX_FILE_BASENAME_LENGTH . ' characters!');
				}
			}

			try {
				if ($file instanceof Folder) {
					$this->getFileStorage()->renameFolder($file, $fileName);
				} else {
					$this->getFileStorage()->renameFile($file, $fileName);
				}
			} catch (\Exception $e) {
				$key = $e instanceof UploadFilterException ? $e->getMessageKey() : null;
				throw new CmsException($key, $e->getMessage(), $e);
			}
		}

		// Custom Properties
		$dirty = false;

		$propertyConfigurations = $this->getFileStorage()->getCustomPropertyConfigurations();
		foreach ($propertyConfigurations as $configuration) {
			$propertyName = $configuration->name;

			if ($request->request->has($propertyName)) {
				$value = $request->request->get($propertyName);

				$property = $this->getFileStorage()->getFileCustomProperty($file, $propertyName);

				$property->setEditableValue($value, $configuration->getEditable());

				$dirty = true;
			}
		}

		if ($dirty) {
			$this->container->getDoctrine()->getManager()->flush();
		}

		$response = array();

		// when changing image private attribute, previews and thumbs will change their paths
		// so we will output new image info
		if ($file instanceof File) {
			$response = $this->imageAndFileOutput($file);
		}

		return new SupraJsonResponse($response);
	}

	/**
	 * Image crop
	 */
	public function cropAction(Request $request)
	{
		$file = $this->getImage('id');

		$post = $request->request;

		if ($post->has('crop') && is_array($post->get('crop'))) {
			$crop = $post->get('crop');
			if (isset($crop['left'], $crop['top'], $crop['width'], $crop['height'])) {
				$left = intval($crop['left']);
				$top = intval($crop['top']);
				$width = intval($crop['width']);
				$height = intval($crop['height']);
				$this->getFileStorage()->cropImage($file, $left, $top, $width, $height);
			}
		}

		$fileData = $this->imageAndFileOutput($file);

		return new SupraJsonResponse($fileData);
	}

	/**
	 * Image rotate
	 */
	public function rotateAction(Request $request)
	{
		$file = $this->getImage();
		$rotate = $request->get('rotate');

		if (isset($rotate) && is_numeric($rotate)) {
			$rotationCount = - intval($_POST['rotate'] / 90);
			$this->getFileStorage()->rotateImage($file, $rotationCount);
		}

		$fileData = $this->imageAndFileOutput($file);

		return new SupraJsonResponse($fileData);
	}

	public function downloadAction(Request $request)
	{
		$requestedFileName = $request->attributes->get('path');

		$headers = array();
		$disposition = ResponseHeaderBag::DISPOSITION_INLINE;

		$file = $this->getFile();

		$mimeType = $file->getMimeType();
		$fileName = $file->getFileName();

		//TODO: is case sensitive comparison OK?
		if ($fileName !== $requestedFileName) {
			throw new ResourceNotFoundException("Requested file name does not match file name on the server");
		}

		if((! $file->isPublic()) && $request->get('inline')) {
			$size = $request->get('size');
			$sizeDir = FileStorage::RESERVED_DIR_SIZE;

			$fileDir = dirname($this->getFileStorage()->getFilesystemPath($file));

			$path = $fileDir . DIRECTORY_SEPARATOR .
				$sizeDir . DIRECTORY_SEPARATOR .
				$size . DIRECTORY_SEPARATOR .
				$file->getFileName();
		} else {
			$path = $this->getFileStorage()->getFilesystemPath($file);
		}

		if ( ! empty($mimeType)) {
			$headers['Content-Type'] = $mimeType;
		}

		if(! $request->get('inline')) {
			$disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
		}

		$response = new BinaryFileResponse($path, 200, $headers, false, $disposition);
		$response->prepare($request);

		return $response;
	}

	/**
	 * Used for view file or image information
	 */
	public function viewAction()
	{
		$node = $this->getFile();

		$nodeOutput = $this->imageAndFileOutput($node);
		$output = array($nodeOutput);

		$return = array(
			'totalRecords' => count($output),
			'records' => $output,
		);

		return new SupraJsonResponse($return);
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
			if (!$request->request->get('filename')) {
				throw new CmsException(null, 'Folder title was not sent');
			}

			$dirName = $request->request->get('filename');
			$parentFolder = null;

			// Adding child folder if parent exists
			if ($request->request->get('parent')) {
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
				$confirmation = $this->getConfirmation('Are You sure?');

				if ($confirmation instanceof Response) {
					return $confirmation;
				}

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
			/* @var $rootNode FileAbstraction */

			if ($request->query->get('type')) {

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
					if ($this->getFileStorage()->fileExists($rootNode)) {
						$sizeName = $this->getFileStorage()->createResizedImage($rootNode, 30, 30, true);
						if ($rootNode->isPublic()) {
							$item['thumbnail'] = $this->getFileStorage()->getWebPath($rootNode, $sizeName);
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

			if ($imageSize instanceof ImageSize) {
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
	 * @param File $file
	 */
	protected function removeFilesRecursively(Folder $file)
	{
		if ($file->hasChildren()) {
			foreach ($file->getChildren() as $childFile) {
				if ($childFile instanceof Folder) {
					$this->removeFilesRecursively($childFile);
				} else {
					$this->removeSingleFile($childFile);
				}
			}
		}

		$this->removeSingleFile($file);
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
