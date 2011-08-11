<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage;
use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\ImageSize;
use Supra\FileStorage\ImageProcessor\ImageResizer;

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

		$filestorage = FileStorage\FileStorage::getInstance();
		
		$fileName = baseName($uploadFile);
		$fileSize = fileSize($uploadFile);
		$file->setName($fileName);
		$file->setSize($fileSize);
		$file->setMimeType($filestorage->getMimeType($uploadFile));
		
		$dir->addChild($file);

		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
		$fileData->setMaster($file);
		$fileData->setTitle(basename($uploadFile));

		$filestorage->storeFileData($file, $uploadFile);

		if ($file->isMimeTypeImage()) {
			$origSize = $file->getImageSize('original');
			$imageProcessor = new ImageResizer();
			$imageInfo = $imageProcessor->getImageInfo($filestorage->getFilesystemPath($file));
			$origSize->setWidth($imageInfo['width']);
			$origSize->setHeight($imageInfo['height']);
			$origSize->setName('');
//			$filestorage->rotateImageLeft($file);
//			$filestorage->rotateImageRight($file);
//			$filestorage->rotateImage180($file);
			$filestorage->createResizedImage($file, 100, 100, true);
			$filestorage->createResizedImage($file, 200, 200, false);
		}

		$filestorage->cropImage($file, 100, 75, 150, 175);
		$filestorage->rotateImageLeft($file);

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
			$this->assertTrue(true);
		} else {
			$this->fail('File path is wrong');
		}
	}

	public function testUploadFilterFail()
	{
		$this->cleanUp();

		// file
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'fail.yml';

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

		try {
			$filestorage->storeFileData($file, $uploadFile);
		} catch (Exception\UploadFilterException $exc) {
			return;
		}

		$this->fail('UploadFilterException should be thrown');
	}

	public function testCreateFolder()
	{
		$this->cleanUp();

		$result = $this->createFolder('testDir');

		if (!($result instanceof \Supra\FileStorage\Entity\Folder)) {
			$this->fail('Failed to create folder');
		}

		$path = $result->getPath(DIRECTORY_SEPARATOR, true);

		if ($path == 'testDir') {
			$this->assertTrue(true);
		} else {
			$this->fail('File path is wrong');
		}
	}

	public function testCreateExistentFolder()
	{
		$this->cleanUp(true);

		$this->createFolder('testDir');

		try {
			$this->createFolder('testDir');
		} catch (Exception\RuntimeException $e) {
			return;
		}

		$this->fail('Test overwrited folder. FileStorage\Exception\RuntimeException should be thrown');
	}

	public function testCreateInvalidFolderWithSymbol()
	{
		$this->cleanUp();

		try {
			$result = $this->createFolder('Copy*Pase');

			$path = $result->getPath(DIRECTORY_SEPARATOR, true);

			if ($path == 'Copy*Pase') {
				$this->fail('Record should not exist in database');
			}

		} catch (Exception\UploadFilterException $e) {
			return;
		}

		$this->fail('On folder create Validation need to throw upload filter exception');
	}

	public function testCreateInvalidFolderWithFirstDot()
	{
		$this->cleanUp();

		try {
			$result = $this->createFolder('.Folder');

			$path = $result->getPath(DIRECTORY_SEPARATOR, true);

			if ($path == '.Folder') {
				$this->fail('Record should not exist in database');
			}
		} catch (Exception\UploadFilterException $e) {
			return;
		}

		$this->fail('On folder create Validation need to throw upload filter exception');
	}
	
	
	public function testCreateInvalidFolderWithFirstUnderscore()
	{
		$this->cleanUp();

		try {
			$result = $this->createFolder('_Folder');

			$path = $result->getPath(DIRECTORY_SEPARATOR, true);

			if ($path == '_Folder') {
				$this->fail('Record should not exist in database');
			}
		} catch (Exception\UploadFilterException $e) {
			return;
		}

		$this->fail('On folder create Validation need to throw upload filter exception');
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

				$path = $dir->getPath(DIRECTORY_SEPARATOR, true);

				if ($path != 'folder') {
					$this->fail('Record should exist in database');
				}

				return;
			}
		} else {
			$this->fail('Wrong entity passed');
		}

	}

	public function testCreateFolderAndRenameItWrong()
	{
		$this->cleanUp();

		$dir = $this->createFolder('testFolder');

		if ($dir instanceof \Supra\FileStorage\Entity\Folder) {
			$filestorage = FileStorage\FileStorage::getInstance();
			try {
				$result = $this->createFolder('Copy*Pase');
			} catch (Exception\UploadFilterException $e) {
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

		$filePath = $filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		self::assertFileExists($filePath, 'Created file doesn\'t exist in file storage');
	}

	public function testCreateExistentFile()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		try {
			$file = $this->createFile();
		} catch (Exception\UploadFilterException $e) {
			return;
		}

		$this->fail('Test overwrited file');
	}

	public function testCreateFileAndRenameIt()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		$filePath = $filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$externalPath = file_exists($filePath);

		if ($externalPath) {

			$filestorage->renameFile($file, 'CHUUUUCK.jpg');
			$renamedFilePath = $filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

			self::assertFileExists($renamedFilePath,'Renamed file should exist in file storage');
		
		} else {
			$this->fail('Created file should exist in file storage');
		}
	}

	public function testCreateFileAndRenameItWrong()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		$externalPath = file_exists($filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true));

		if ($externalPath) {
			try {
				$filestorage->renameFile($file, 'CHUU*UUCK.jpg');
			} catch (Exception\UploadFilterException $e) {
				return;
			}
		} else {
			$this->fail('File should exist in file storage');
		}
	}

	public function testCreateFileAndRenameItRightButWithWrongExtension()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();
		$oldName = $file->getName();

		$externalPath = $filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$externalPathResult = file_exists($externalPath);

		$catched = false;

		if ($externalPathResult) {
			try {
				$filestorage->renameFile($file, 'CHUUUCK.png');
			} catch (Exception\UploadFilterException $exc) {
				$catched = true;
			}
		} else {
			$this->fail('File should exist in file storage');
		}

		if (($file->getName() == $oldName) && $catched) {
			return;
		} else {
			$this->fail('File extension should be the same and rename should throw Exception\UploadFilterException');
		}
	}

	/**
	 * @return \Supra\FileStorage\Entity\File
	 */
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
		$this->cleanUp(true);

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

	public function testCreateMultiLevelFolderAndUploadFile()
	{
		$this->cleanUp(true);

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

		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';
		self::getConnection()->flush();
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

		if ($internalPath && $externalPath) {
			$path = $file->getPath(DIRECTORY_SEPARATOR, true);
			$filePath = 'one/two/three/chuck.jpg';
			$fileFullPath = $filestorage->getExternalPath() . $filePath;
			if ( ! ($path == $filePath) || ! file_exists($fileFullPath)) {
				$this->fail('File path is wrong');
			}

		} else {
			$this->fail('There is no folders');
		}
	}

	public function testCreateMultiLevelFolderWithFilesAndSetPrivate() {
		$this->cleanUp(true);

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
				self::getConnection()->flush();
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

				$filestorage->storeFileData($file, $uploadFile);
				self::getConnection()->flush();

		}
		self::getConnection()->flush();
		/*@var $filestorage \Supra\FileStorage */
		$em = $filestorage->getEntityManager();
		$repo = $em->getRepository('Supra\FileStorage\Entity\Folder');
		$node = $repo->findOneByFileName('one');

		$filestorage->setPrivate($node);
		self::getConnection()->flush();
		$filePath = $filestorage->getInternalPath() . 'one/two/three/chuck.jpg';

		self::assertFileExists($filePath, 'File should exist in internal storage');
	}

	public function testCreateFileAndMoveItToPrivate()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		$filestorage->setPrivate($file);
		self::getConnection()->flush();

		$fileInternalPath = $filestorage->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$fileExternalPath = $filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		self::assertFileExists($fileInternalPath, 'Created file doesn\'t exist in file storage');
		
		self::assertFileNotExists($fileExternalPath, 'Created file DOES exist in external path after making it private');
	}

	public function testCreateFileAndMoveItToPrivateThenBackToPublic()
	{
		$this->cleanUp();
		$filestorage = FileStorage\FileStorage::getInstance();
		$file = $this->createFile();

		$filestorage->setPrivate($file);
		self::getConnection()->flush();

		$filestorage->setPublic($file);
		self::getConnection()->flush();

		$filePath = $filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		self::assertFileExists($filePath, 'Created file doesn\'t exist in file storage');
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
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\ImageSize");
		$query->execute();
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\Abstraction\File");
		$query->execute();

		if (self::DELETE_FILES || $delete) {
			$this->deleteFilesAndFolders();
		}
		
		// Detach all entities
		self::getConnection()->clear();
	}
	
	public function testReplaceFile() {
		
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
		
		// replace
		$replaceFile = __DIR__ . DIRECTORY_SEPARATOR . 'JohnMclane.jpg';

		$fileName = baseName($replaceFile);
		$fileSize = fileSize($replaceFile);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $replaceFile);
		finfo_close($finfo);

		$replaceFileInfo = array(
			'name' => $fileName,
			'type' => $mimeType,
			'size' => $fileSize,
			'tmp_name' => $replaceFile,
		);

		$filestorage->replaceFile($file, $replaceFileInfo);	
		
		$replacedFilePath = $filestorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		
		self::getConnection()->flush();
		
		self::assertFileExists($replacedFilePath, 'JohnMclane.jpg should replace chuck.jpg. But it\'s obvious that nobody cant replace Chuck Norris');
	}
	
	public function testFileRepository()
	{
		$this->cleanUp(true);
		
		$dir1 = $this->createFolder('one');
		$file1 = $this->createFile();
		$dir1->addChild($file1);
		
		$dir2 = $this->createFolder('two');
		$file2 = $this->createFile();
		$dir2->addChild($file2);
		
		$dir = $this->createFolder('three');
		
		self::getConnection()->flush();
		
		// Must be two files
		$fileRepo = self::getConnection()->getRepository('Supra\FileStorage\Entity\File');
		$files = $fileRepo->findAll();
		self::assertEquals(2, count($files));
		
		// One parent folder
		$file = $files[0];
		$folders = $file->getAncestors();
		
		self::assertEquals(1, count($folders));
		
		// 3 folders
		$folderRepo = self::getConnection()->getRepository('Supra\FileStorage\Entity\Folder');
		$folders = $folderRepo->findAll();
		self::assertEquals(3, count($folders));
		
		// 5 elements in total
		$allRepo = self::getConnection()->getRepository('Supra\FileStorage\Entity\Abstraction\File');
		$all = $allRepo->findAll();
		self::assertEquals(5, count($all));
	}
	
	public function testCleanUp()
	{
		$this->cleanUp();
	}

}
