<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage;
use Supra\ObjectRepository\ObjectRepository;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class FileStorageTestLocal extends \PHPUnit_Framework_TestCase
{

	const DELETE_FILES = true;
	
	/**
	 * @var FileStorage\FileStorage
	 */
	private $fileStorage;
	
	public function setUp()
	{
		$this->fileStorage = ObjectRepository::getFileStorage($this);
	}

	private static function getConnection()
	{
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();
	}

	private function deleteFilesAndFolders()
	{
		$this->removeFolders($this->fileStorage->getExternalPath());
		$this->removeFolders($this->fileStorage->getInternalPath());
	}

	private function removeFolders($dir)
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

	private function cleanUp($delete = false)
	{
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\MetaData");
		$query->execute();
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\Abstraction\File");
		$query->execute();

		if (self::DELETE_FILES || $delete) {
			$this->deleteFilesAndFolders();
		}
	}

	private function createFolder($name)
	{
		$dir = new \Supra\FileStorage\Entity\Folder();

		$dir->setFileName($name);

		self::getConnection()->persist($dir);
		self::getConnection()->flush();

		$this->fileStorage->createFolder($dir);

		$internalPath = is_dir($this->fileStorage->getInternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));
		$externalPath = is_dir($this->fileStorage->getExternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));

		if ($internalPath && $externalPath) {
			return $dir;
		} else {
			return null;
		}
	}

	private function createFile($dir = null)
	{
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';

		$file = new \Supra\FileStorage\Entity\Image();
		self::getConnection()->persist($file);

		$fileName = baseName($uploadFile);
		$fileSize = fileSize($uploadFile);
		$file->setFileName($fileName);
		$file->setSize($fileSize);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadFile);
		finfo_close($finfo);
		$file->setMimeType($mimeType);
		
		$file->setWidth(1);
		$file->setHeight(1);
		
		if ( ! empty($dir)) {
			$dir->addChild($file);
		}

		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
		$fileData->setMaster($file);
		$fileData->setTitle(basename($uploadFile));

		$this->fileStorage->storeFileData($file, $uploadFile);

		self::getConnection()->flush();

		return $file;
	}

	public function testReplaceFile()
	{
		$this->cleanUp(true);


		$dir = $this->createFolder('dir');
		$fileEntity = $this->createFile($dir);

		$fileFullPath = $this->fileStorage->getExternalPath() . $fileEntity->getPath(DIRECTORY_SEPARATOR, true);
		$fileExists = file_exists($fileFullPath);

		
		$fileToReplacePath = __DIR__ . DIRECTORY_SEPARATOR . 'JohnMclane.jpg';
		$fileToReplace = $this->fileInfo($fileToReplacePath);

		if ($fileExists && ! is_null($fileToReplace)) {
			$this->fileStorage->replaceFile($fileEntity, $fileToReplace);
		}
	}

	private function fileInfo($path)
	{
		$output = null;
		
		if (file_exists($path)) {
			$fileName = baseName($path);
			$fileSize = fileSize($path);

			$finfo = finfo_open(FILEINFO_MIME_TYPE);

			$mimeType = finfo_file($finfo, $path);

			finfo_close($finfo);

			$output = array(
				'name' => $fileName,
				'type' => $mimeType,
				'size' => $fileSize,
				'tmp_name' => $path,
			);
		}

		return $output;
	}

}
