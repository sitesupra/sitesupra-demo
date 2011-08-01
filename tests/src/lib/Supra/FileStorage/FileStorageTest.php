<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage;
use Supra\FileStorage\UploadFilter;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class FileStorageTest extends \PHPUnit_Extensions_OutputTestCase
{

	const DELETE_FILES = true;

	private static function getConnection()
	{
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();

	}

	public function testUploadFileToInternal()
	{
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

	}

	public function testUploadFilterFail()
	{
		$this->cleanUp();

		// file
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'fail.yaml';

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

		$filestorage = FileStorage\FileStorage::getInstance();

		$failed = false;
		try {
			$filestorage->storeFileData($file, $uploadFile);
		} catch (UploadFilter\UploadFilterException $e) {
			$failed = true;
		}

		$this->assertTrue($failed);

	}

	public function testCreateFolder()
	{
		$this->cleanUp();

		$result = $this->createFolder('testDir');

		if ($result instanceof \Supra\FileStorage\Entity\Folder) {
			$this->assertTrue(true);
		} else {
			$this->fail('Failed to create folder');
		}

	}

	public function testCreateExistentFolder()
	{
		$this->cleanUp(true);

		$this->createFolder('testDir');

		try {
			$this->createFolder('testDir');
		} catch (FileStorage\FileStorageException $e) {
			$this->assertTrue(true);
			return;
		}

		$this->fail('Test overwrited folder');

	}

	public function testCreateInvalidFolderWithSymbol()
	{
		$this->cleanUp();

		try {
			$result = $this->createFolder('Copy*Pase');
		} catch (\Supra\FileStorage\Helpers\FileStorageHelpersException $e) {
			$this->assertTrue(true);
			return;
		}

		$this->fail('Created file should exist in file storage');

	}

	public function testCreateInvalidFolderWithFirstDot()
	{
		$this->cleanUp();

		try {
			$result = $this->createFolder('.Folder');
		} catch (\Supra\FileStorage\Helpers\FileStorageHelpersException $e) {
			$this->assertTrue(true);
			return;
		}

		$this->assertTrue(false);

	}

	public function testCreateFolderAndRenameIt()
	{
		$this->cleanUp();

		$dir = $this->createFolder('testFolder');

		if ($dir instanceof \Supra\FileStorage\Entity\Folder) {
			$filestorage = FileStorage\FileStorage::getInstance();
			$filestorage->renameFolder($dir, 'folder');

			$internalPath = is_dir($filestorage->getInternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));
			$externalPath = is_dir($filestorage->getExternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));

			if ($internalPath && $externalPath) {
				$this->assertTrue(true);
				return;
			} else {
				$this->assertTrue(false);
			}
		}

		$this->assertTrue(false);

	}

	public function testCreateFolderAndRenameItWrong()
	{
		$this->cleanUp();

		$dir = $this->createFolder('testFolder');

		if ($dir instanceof \Supra\FileStorage\Entity\Folder) {
			$filestorage = FileStorage\FileStorage::getInstance();
			try {
				$result = $this->createFolder('Copy*Pase');
			} catch (\Supra\FileStorage\Helpers\FileStorageHelpersException $e) {
				$this->assertTrue(true);
				return;
			}
		}

		$this->fail('Created file should exist in file storage');

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

	public function testCreateFile()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		$internalPath = file_exists($filestorage->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true));

		if ($internalPath) {
			$this->assertTrue(true);
		} else {
			$this->fail('Created file should exist in file storage');
		}

	}

	public function testCreateExistentFile()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		try {
			$file = $this->createFile();
		} catch (UploadFilter\UploadFilterException $e) {
			$this->assertTrue(true);
			return;
		}

		$this->fail('Test overwrited file');

	}

	public function testCreateFileAndRenameIt()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		$internalPath = file_exists($filestorage->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true));

		if ($internalPath) {

			$filestorage->renameFile($file, 'CHUUUUCK.jpg');
			$renamedFilePath = $filestorage->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
			if (file_exists($renamedFilePath)) {
				$this->assertTrue(true);
				return;
			} else {
				$this->fail('Renamed file should exist in file storage');
			}
		} else {
			$this->fail('Created file should exist in file storage');
		}

	}

	public function testCreateFileAndRenameItWrong()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		$internalPath = file_exists($filestorage->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true));

		if ($internalPath) {
			try {
				$filestorage->renameFile($file, 'CHUU*UUCK.jpg');
			} catch (\Supra\FileStorage\Helpers\FileStorageHelpersException $e) {
				$this->assertTrue(true);
				return;
			}
		} else {
			$this->fail('File should exist in file storage');
		}

		$this->fail('Something went wrong');

	}

	public function testCreateFileAndRenameItRightButWithWrongExtension()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();
		$oldName = $file->getName();

		$internalPath = file_exists($filestorage->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true));

		$catched = false;

		if ($internalPath) {
			try {
				$filestorage->renameFile($file, 'CHUUUCK.png');
			} catch (FileStorage\FileStorageException $exc) {
				$catched = true;
			}
		} else {
			$this->fail('File should exist in file storage');
		}

		if (($file->getName() == $oldName) && $catched) {
			$this->assertTrue(true);
		} else {
			$this->fail('File extension should be the same and rename should throw FileStorageException');
		}

	}

	private function createFile()
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

		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
		$fileData->setMaster($file);
		$fileData->setTitle(basename($uploadFile));

		$filestorage = FileStorage\FileStorage::getInstance();
		$filestorage->storeFileData($file, $uploadFile);

		self::getConnection()->flush();

		return $file;

	}

	public function testCreateMultiLevelFolder()
	{
		$this->cleanUp();

		$filestorage = FileStorage\FileStorage::getInstance();

		$dir = null;
		$dirNames = array('one', 'two', 'three');
		foreach ($dirNames as $dirName) {
			$parentDir = $dir;
			$dir = new \Supra\FileStorage\Entity\Folder();
			$dir->setName($dirName);
			self::getConnection()->persist($dir);
			self::getConnection()->flush();
			if ($parentDir instanceof \Supra\FileStorage\Entity\Folder) {
				$parentDir->addChild($dir);
				$filestorage->createFolder($parentDir->getPath(DIRECTORY_SEPARATOR, true), $dirName);
			} else {
				$filestorage->createFolder($dir->getPath(DIRECTORY_SEPARATOR, false), $dirName);
			}
		}

		$internalPath = is_dir($filestorage->getInternalPath() . 'one/two/three');
		$externalPath = is_dir($filestorage->getExternalPath() . 'one/two/three');

		if ($internalPath && $externalPath) {
			$this->assertTrue(true);
		} else {
			$this->fail('There is no folders');
		}

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
					if (filetype($dir . "/" . $object) == "dir") {
						$this->removeFolders($dir . "/" . $object);
					} else {
						@unlink($dir . "/" . $object);
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

}
