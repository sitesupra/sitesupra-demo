<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Entity\Abstraction\File as FileAbstraction;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\Entity\Folder;
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\ImageSize;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\FileStorage\Exception\UploadFilterException;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\Repository\FileNestedSetRepository;
use Symfony\Component\HttpFoundation\Request;

class MediaLibraryController extends Controller
{
	protected $application = 'media-library';

	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
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
			if (!$request->request->has('parent')) {
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

				$knownExtensions = $this->getApplicationConfigValue('knownFileExtensions', array());
				if (in_array($extension, $knownExtensions)) {
					$item['known_extension'] = $extension;
				}

				$checkExistance = $this->getApplicationConfigValue('checkFileExistence');
				if ($checkExistance == ApplicationConfiguration::CHECK_FULL) {
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
