<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class FileStorageTestLocal extends \PHPUnit_Extensions_OutputTestCase
{

	const DELETE_FILES = true;

	private static function getConnection()
	{
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();
	}

	private function deleteFilesAndFolders()
	{
		$filestorage = FileStorage\FileStorage::getInstance();
		$this->removeFolders($filestorage->getExternalPath());
		$this->removeFolders($filestorage->getInternalPath());
	}

	private function removeFolders($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != ".." && $object != ".svn") {
					$dir = rtrim($dir, DIRECTORY_SEPARATOR);
					if (filetype($dir . DIRECTORY_SEPARATOR . $object) == "dir") {
						$this->removeFolders($dir . DIRECTORY_SEPARATOR . $object);
					} else {
						@unlink($dir . DIRECTORY_SEPARATOR . $object);
					}
				}
			}
			reset($objects);
			@rmdir($dir);
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
		$filestorage = FileStorage\FileStorage::getInstance();

		$dir->setName($name);

		self::getConnection()->persist($dir);
		self::getConnection()->flush();

		$filestorage->createFolder($dir->getPath(DIRECTORY_SEPARATOR, false), $name);

		$internalPath = is_dir($filestorage->getInternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));
		$externalPath = is_dir($filestorage->getExternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));

		if ($internalPath && $externalPath) {
			return $dir;
		} else {
			return null;
		}
	}

	private function createFile($dir = null)
	{
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';

		$file = new \Supra\FileStorage\Entity\File();
		self::getConnection()->persist($file);

		$fileName = baseName($uploadFile);
		$fileSize = fileSize($uploadFile);
		$file->setName($fileName);
		$file->setSize($fileSize);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadFile);
		finfo_close($finfo);
		$file->setMimeType($mimeType);

		if ( ! empty($dir)) {
			$dir->addChild($file);
		}

		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
		$fileData->setMaster($file);
		$fileData->setTitle(basename($uploadFile));

		$filestorage = FileStorage\FileStorage::getInstance();
		$filestorage->storeFileData($file, $uploadFile);

		self::getConnection()->flush();

		return $file;
	}

	public function testReplaceFile()
	{
		$this->cleanUp(true);

		$filestorage = FileStorage\FileStorage::getInstance();

		$dir = $this->createFolder('dir');
		$fileEntity = $this->createFile($dir);

		$fileFullPath = $filestorage->getExternalPath() . $fileEntity->getPath(DIRECTORY_SEPARATOR, true);
		$fileExists = file_exists($fileFullPath);

		
		$fileToReplacePath = __DIR__ . DIRECTORY_SEPARATOR . 'JohnMclane.jpg';
		$fileToReplace = $this->fileInfo($fileToReplacePath);

		if ($fileExists && ! is_null($fileToReplace)) {
			$filestorage->replaceFile($fileEntity, $fileToReplace);
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
