<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage;
use Supra\FileStorage\UploadFilter;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class FileStorageTest extends \PHPUnit_Extensions_OutputTestCase {
	const DELETE_FILES = true;

	private static function getConnection() {
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();
	}


//	public function testPublicPrivate() {
//		$this->cleanUp(true);
//
//		// directories
//
//		$dir = $this->createFolder('one');
//
//		self::getConnection()->flush();
//
//		// file
//		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';
//
//		$file = new \Supra\FileStorage\Entity\File();
//		self::getConnection()->persist($file);
//
//		$fileName = baseName($uploadFile);
//		$fileSize = fileSize($uploadFile);
//		$file->setName($fileName);
//		$file->setSize($fileSize);
//		$finfo = finfo_open(FILEINFO_MIME_TYPE);
//		$mimeType = finfo_file($finfo, $uploadFile);
//		finfo_close($finfo);
//		$file->setMimeType($mimeType);
//
//		$dir->addChild($file);
//
//		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
//		$fileData->setMaster($file);
//		$fileData->setTitle(basename($uploadFile));
//
//		$filestorage = FileStorage\FileStorage::getInstance();
//		$filestorage->storeFileData($file, $uploadFile);
//
//		self::getConnection()->flush();
//
////		// meta-data getter test
////		\Log::debug(array(
////				'File __toString(): ' => $file->__toString(),
////				'File getTitle(): ' => $file->getTitle('ru'),
////				'File getDescription(): ' => $file->getDescription(),
////			));
////		$repo = self::getConnection()->getRepository("Supra\FileStorage\Entity\Folder");
////		$roots = $repo->getRootNodes();
////		1+1;
//
//		$path = $file->getPath(DIRECTORY_SEPARATOR, true);
//		if ($path == 'one/chuck.jpg') {
//			$filestorage->setPrivate($file);
//			self::getConnection()->flush();
//			$filestorage->setPublic($file);
//			self::getConnection()->flush();
//			$this->assertTrue(true);
//		} else {
//			$this->fail('File path is wrong');
//		}
//	}

		public function testPublicPrivate() {
		$this->cleanUp(true);

		// directories

		$dir = $this->createFolder('one');

		self::getConnection()->flush();

		// file
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

		$dir->addChild($file);

		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
		$fileData->setMaster($file);
		$fileData->setTitle(basename($uploadFile));

		$filestorage = FileStorage\FileStorage::getInstance();
		$filestorage->storeFileData($file, $uploadFile);

		self::getConnection()->flush();

//		// meta-data getter test
//		\Log::debug(array(
//				'File __toString(): ' => $file->__toString(),
//				'File getTitle(): ' => $file->getTitle('ru'),
//				'File getDescription(): ' => $file->getDescription(),
//			));
//		$repo = self::getConnection()->getRepository("Supra\FileStorage\Entity\Folder");
//		$roots = $repo->getRootNodes();
//		1+1;

		$path = $file->getPath(DIRECTORY_SEPARATOR, true);
		if ($path == 'one/chuck.jpg') {
			$filestorage->setPrivate($dir);
			$this->assertTrue(true);
		} else {
			$this->fail('File path is wrong');
		}
	}
	private function deleteFilesAndFolders() {
		$filestorage = FileStorage\FileStorage::getInstance();
		$this->removeFolders($filestorage->getExternalPath());
		$this->removeFolders($filestorage->getInternalPath());
	}

	private function removeFolders($dir) {
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

	private function cleanUp($delete = false) {
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\MetaData");
		$query->execute();
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\Abstraction\File");
		$query->execute();

		if (self::DELETE_FILES || $delete) {
			$this->deleteFilesAndFolders();
		}
	}
//
//	public function testCleanUp() {
//		$this->cleanUp();
//	}

	private function createFolder($name) {

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

	private function createFile() {
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

		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
		$fileData->setMaster($file);
		$fileData->setTitle(basename($uploadFile));

		$filestorage = FileStorage\FileStorage::getInstance();
		$filestorage->storeFileData($file, $uploadFile);

		self::getConnection()->flush();

		return $file;
	}

}
