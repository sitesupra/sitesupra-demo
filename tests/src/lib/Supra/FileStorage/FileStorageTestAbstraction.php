<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage;
use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\ImageSize;
use Supra\FileStorage\ImageProcessor\ImageResizer;
use Supra\ObjectRepository\ObjectRepository;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class FileStorageTestAbstraction extends \PHPUnit_Framework_TestCase
{
	const DELETE_FILES = true;

	/**
	 * @var FileStorage\FileStorage
	 */
	protected $fileStorage;

	public function setUp()
	{
		$this->fileStorage = ObjectRepository::getFileStorage($this);
	}

	protected function cleanUp($delete = false)
	{
		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$query = $em->createQuery("delete from Supra\FileStorage\Entity\ImageSize");
		$query->execute();
		$query = $em->createQuery("delete from Supra\FileStorage\Entity\Abstraction\File");
		$query->execute();
		$query = $em->createQuery("delete from Supra\FileStorage\Entity\FilePath");
		$query->execute();

		if (self::DELETE_FILES || $delete) {
			$this->deleteFilesAndFolders();
		}

		// Detach all entities
		$em->clear();
	}

	protected function deleteFilesAndFolders()
	{
		$this->removeFolders($this->fileStorage->getExternalPath());
		$this->removeFolders($this->fileStorage->getInternalPath());
	}

	protected function removeFolders($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);

			foreach ($objects as $object) {
				if ($object[0] != ".") {
					$filename = $dir . DIRECTORY_SEPARATOR . $object;
					if (is_dir($filename)) {
						$this->removeFolders($filename);
						rmdir($filename);
					} else {
						unlink($filename);
					}
				}
			}
		}
	}

	/**
	 * @return \Supra\FileStorage\Entity\File
	 */
	protected function createFile($nameSuffix = '')
	{
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . "chuck.jpg";

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$file = new \Supra\FileStorage\Entity\File();
		$em->persist($file);

		$fileName = str_replace('.', $nameSuffix . '.', baseName($uploadFile));
		$fileSize = fileSize($uploadFile);
		$file->setFileName($fileName);
		$file->setSize($fileSize);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadFile);
		finfo_close($finfo);
		$file->setMimeType($mimeType);

		$this->fileStorage->storeFileData($file, $uploadFile);

		$em->flush();

		return $file;
	}

	protected function createFolder($name)
	{

		$dir = new \Supra\FileStorage\Entity\Folder();

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$dir->setFileName($name);

		$em->persist($dir);
		$em->flush();

		$this->fileStorage->createFolder($dir);

		$internalPath = is_dir($this->fileStorage->getInternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));
		$externalPath = is_dir($this->fileStorage->getExternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));

		if ($internalPath && $externalPath) {
			return $dir;
		} else {
			return null;
		}
	}

	/**
	 * @return FileStorage\Entity\Image
	 */
	protected function createImage($nameSuffix = '')
	{
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . "chuck.jpg";

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$image = new \Supra\FileStorage\Entity\Image();
		$em->persist($image);

		$fileName = str_replace('.', $nameSuffix . '.', baseName($uploadFile));
		$fileSize = filesize($uploadFile);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadFile);
		finfo_close($finfo);

		$image->setFileName($fileName);
		$image->setSize($fileSize);
		$image->setMimeType($mimeType);


		$imageProcessor = new ImageResizer();
		$imageInfo = getimagesize($uploadFile);
		$image->setWidth($imageInfo[0]);
		$image->setHeight($imageInfo[0]);

		$this->fileStorage->validateFileUpload($image, $uploadFile);

		try {
			$this->fileStorage->storeFileData($image, $uploadFile);
		} catch (\Exception $e) {
			$em->flush();
			$em->remove($image);
			$em->flush();

			throw $e;
		}

		$em->flush();
		
		return $image;
	}

}