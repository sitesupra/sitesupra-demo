<?php

namespace Supra\Cms\MediaLibrary\Medialibrary;

use Supra\FileStorage\ImageProcessor;
use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity;
use Supra\Cms\MediaLibrary\MediaLibraryAbstractAction;
use Supra\Cms\Exception\CmsException;
use Supra\FileStorage\Entity\Folder;
use Supra\Cms\MediaLibrary\ApplicationConfiguration;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Configuration\PropertyConfiguration;

class MedialibraryAction extends MediaLibraryAbstractAction
{
	// types for MediaLibrary UI


	
	const DUPLICATE_NAME_PATTERN = '%s (%d).%s';

	//
	const MAX_FILE_BASENAME_LENGTH = 100;





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

		$this->getResponse()->setResponseData($return);
	}



	/**
	 * Used for new folder creation
	 */


	/**
	 * Used for folder or file renaming
	 */
	public function saveAction()
	{
		$this->isPostRequest();

		$file = $this->getEntity();

		// set private
		if ($this->hasRequestParameter('private')) {
			$private = $this->getRequestParameter('private');

			if ($private == 0) {
				$this->fileStorage->setPublic($file);
				$this->writeAuditLog('%item% was set as public', $file);
			}

			if ($private == 1) {
				$this->fileStorage->setPrivate($file);
				$this->writeAuditLog('%item% was set as private', $file);
			}

			$this->entityManager->flush();
			$this->getResponse()->setResponseData(null);
			return;
		}

		// renaming
		if ($this->hasRequestParameter('filename')) {

			$fileName = $this->getRequestParameter('filename');

			if (trim($fileName) == '') {
				throw new CmsException(null, 'Empty filename not allowed');
			}

			$originalFileInfo = pathinfo($file->getFileName());

			$newFileInfo = pathinfo($fileName);

			if (mb_strlen($newFileInfo['basename'], 'utf-8') > 100) {

				if ($file instanceof Entity\Folder) {
					throw new CmsException(null, 'Folder name is too long! Maximum length is ' . self::MAX_FILE_BASENAME_LENGTH . ' characters!');
				} else {
					throw new CmsException(null, 'File name is too long! Maximum length is ' . self::MAX_FILE_BASENAME_LENGTH . ' characters!');
				}
			}

			if ($file instanceof Entity\Folder) {
				$this->fileStorage->renameFolder($file, $fileName);
			} else {
				$this->fileStorage->renameFile($file, $fileName);
			}
		}
		
		// Custom Properties
		$dirty = false;
		
		$input = $this->getRequestInput();
		/* @var $input \Supra\Request\RequestData */
		$propertyConfigurations = $this->fileStorage->getCustomPropertyConfigurations();
		foreach ($propertyConfigurations as $configuration) {
			/* @var $configuration PropertyConfiguration */
			
			$propertyName = $configuration->name;
			
			if ($input->offsetExists($propertyName)) {
				$value = $input->offsetGet($propertyName);

				$property = $this->fileStorage->getFileCustomProperty($file, $propertyName);
				
				$property->setEditableValue($value, $configuration->getEditable());
				
				$dirty = true;
			}
		}
		
		if ($dirty) {
			$this->entityManager->flush();
		}

		$this->writeAuditLog('%item% saved', $file);

		$response = array();

		// when changing image private attribute, previews and thumbs will change their paths
		// so we will output new image info
		if ($file instanceof Entity\File) {
			$response = $this->imageAndFileOutput($file);
        }

		$this->getResponse()
				->setResponseData($response);
	}

	/**
	 * @param Entity\File $file
	 */
	protected function removeFilesRecursively(Entity\Folder $file)
	{
		if ($file->hasChildren()) {

			foreach ($file->getChildren() as $childFile) {

				if ($childFile instanceof Entity\Folder) {
					$this->removeFilesRecursively($childFile);
				} else {
					$this->removeSingleFile($childFile);
				}
			}
		}

		$this->removeSingleFile($file);
	}

	public function moveAction()
	{
		$repository = $this->entityManager->getRepository(Entity\Abstraction\File::CN());
		/* @var $repository \Supra\FileStorage\Repository\FileNestedSetRepository */
		$repository->getNestedSetRepository()->lock();

		$this->isPostRequest();
		$file = $this->getEntity();

//		$this->checkActionPermission($file, Entity\Abstraction\File::PERMISSION_DELETE_NAME);		
		$parentId = $this->getRequestParameter('parent_id');

		$target = null;
		if ( ! empty($parentId)) {
			$target = $this->entityManager->getRepository(Entity\Abstraction\File::CN())
					->findOneById($parentId);
		}

		if (is_null($file)) {
			$this->getResponse()->setErrorMessage('File doesn\'t exist anymore');
		}

		// try to move
		try {
			$this->fileStorage->move($file, $target);
		} catch (Exception\RuntimeException $e) {
			throw new CmsException(null, $e->getMessage());
		}

		$this->writeAuditLog('%item% moved', $file);
	}



	/**
	 * Image rotate
	 */
	public function imagerotateAction()
	{
		$this->isPostRequest();
		$file = $this->getImage();

		if (isset($_POST['rotate']) && is_numeric($_POST['rotate'])) {
			$rotationCount = - intval($_POST['rotate'] / 90);
			$this->fileStorage->rotateImage($file, $rotationCount);
		}

		$fileData = $this->imageAndFileOutput($file);
		$this->writeAuditLog('%item% rotated', $file);
		$this->getResponse()->setResponseData($fileData);
	}

	/**
	 * Image crop
	 */
	public function imagecropAction()
	{
		$this->isPostRequest();
		$file = $this->getImage('id');

		if (isset($_POST['crop']) && is_array($_POST['crop'])) {
			$crop = $_POST['crop'];
			if (isset($crop['left'], $crop['top'], $crop['width'], $crop['height'])) {
				$left = intval($crop['left']);
				$top = intval($crop['top']);
				$width = intval($crop['width']);
				$height = intval($crop['height']);
				$this->fileStorage->cropImage($file, $left, $top, $width, $height);
			}
		}

		$fileData = $this->imageAndFileOutput($file);
		$this->writeAuditLog('%item% cropped', $file);
		$this->getResponse()->setResponseData($fileData);
	}



	/**
	 * Helper method to fetch config value from ApplicationConfig class
	 * for media library
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	private function getApplicationConfigValue($key, $default = null)
	{
		$appConfig = ObjectRepository::getApplicationConfiguration($this);

		if ($appConfig instanceof ApplicationConfiguration) {
			if (property_exists($appConfig, $key)) {
				return $appConfig->$key;
			}
		}

		if ( ! is_null($default)) {
			return $default;
		}

		return null;
	}



}