<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage;
use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\ImageSize;
use Supra\FileStorage\ImageProcessor\ImageResizer;
use Supra\ObjectRepository\ObjectRepository;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for File Storage
 */
class FileStorageTest extends \PHPUnit_Framework_TestCase
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
	
	public function testNestedSet()
	{
		$rand = rand();
		
		$fileA = $this->createFile($rand);
		
		$lft = $fileA->getLeftValue();
		$rgt = $fileA->getRightValue();
		$lvl = $fileA->getLevel();
		
		$this->fileStorage->remove($fileA);
		
		$fileA = $this->createFile($rand);
		
		self::assertEquals($lft, $fileA->getLeftValue());
		self::assertEquals($rgt, $fileA->getRightValue());
		self::assertEquals($lvl, $fileA->getLevel());
	}

	public function testUploadFileToInternal()
	{
		$this->cleanUp(true);

		// directories

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$dir = $this->createFolder('one');

		$em->flush();

		// file
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';

		$mimeType = $this->fileStorage->getMimeType($uploadFile);
		
		$file = null;
		if ($this->fileStorage->isMimeTypeImage($mimeType)) {
			$file = new \Supra\FileStorage\Entity\Image;
		} else {
			$file = new \Supra\FileStorage\Entity\File();
		}
		$em->persist($file);

		$fileName = baseName($uploadFile);
		$fileSize = fileSize($uploadFile);
		$file->setFileName($fileName);
		$file->setSize($fileSize);
		$file->setMimeType($mimeType);

		if ($file instanceof \Supra\FileStorage\Entity\Image) {
			$imageProcessor = new ImageResizer();
			$imageInfo = $imageProcessor->getImageInfo($uploadFile);
			$file->setWidth($imageInfo['width']);
			$file->setHeight($imageInfo['height']);
		}
		
		$dir->addChild($file);

		$this->fileStorage->storeFileData($file, $uploadFile);

		if ($file instanceof \Supra\FileStorage\Entity\Image) {
//			$this->fileStorage->rotateImageLeft($file);
//			$this->fileStorage->rotateImageRight($file);
//			$this->fileStorage->rotateImage180($file);
			$this->fileStorage->createResizedImage($file, 100, 100, true);
			$this->fileStorage->createResizedImage($file, 200, 200, false);
		}

		$this->fileStorage->cropImage($file, 100, 75, 150, 175);
		$this->fileStorage->rotateImageLeft($file);

//		// meta-data getter test
//		\Log::debug(array(
//				'File __toString(): ' => $file->__toString(),
//				'File getTitle(): ' => $file->getTitle('ru'),
//				'File getDescription(): ' => $file->getDescription(),
//			));
//		$repo = self::getConnection()->getRepository("Supra\FileStorage\Entity\Folder");
//		$roots = $repo->getRootNodes();
//		1+1;
//		$this->fileStorage->setPrivate($file);
//		$this->fileStorage->setPublic($file);

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

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		// file
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'fail.bat';

		$file = new \Supra\FileStorage\Entity\File();

		$em->persist($file);

		$fileName = baseName($uploadFile);
		$fileSize = fileSize($uploadFile);
		$file->setFileName($fileName);
		$file->setSize($fileSize);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadFile);
		finfo_close($finfo);
		$file->setMimeType($mimeType);

		try {
			$this->fileStorage->storeFileData($file, $uploadFile);
		} catch (Exception\UploadFilterException $exc) {
			return;
		}

		$this->fail('UploadFilterException should be thrown');
	}

	public function testCreateFolder()
	{
		$this->cleanUp();

		$result = $this->createFolder('testDir');

		if ( ! ($result instanceof \Supra\FileStorage\Entity\Folder)) {
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
		} catch (Exception\UploadFilterException $e) {
			return;
		}

		$this->fail('Test overwrited folder. FileStorage\Exception\RuntimeException should be thrown');
	}

	public function testCreateExistentFolderWithDifferentCases()
	{
		$this->cleanUp(true);

		$this->createFolder('testDir');

		try {
			$this->createFolder('TestDir');
		} catch (Exception\UploadFilterException $e) {
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
			$this->fileStorage->renameFolder($dir, 'folder');

			$internalPath = is_dir($this->fileStorage->getInternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));
			$externalPath = is_dir($this->fileStorage->getExternalPath() . $dir->getPath(DIRECTORY_SEPARATOR, true));

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

	public function testCreateFile()
	{
		$this->cleanUp();
		$file = $this->createFile();

		$filePath = $this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		self::assertFileExists($filePath, 'Created file doesn\'t exist in file storage');
	}

	public function testCreateExistentFile()
	{
		$this->cleanUp();
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
		$file = $this->createFile();

		$filePath = $this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$externalPath = file_exists($filePath);

		if ($externalPath) {

			$this->fileStorage->renameFile($file, 'CHUUUUCK.jpg');
			$renamedFilePath = $this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

			self::assertFileExists($renamedFilePath, 'Renamed file should exist in file storage');
		} else {
			$this->fail('Created file should exist in file storage');
		}
	}

	public function testCreateFileAndRenameItWrong()
	{
		$this->cleanUp();
		$file = $this->createFile();

		$externalPath = file_exists($this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true));

		if ($externalPath) {
			try {
				$this->fileStorage->renameFile($file, 'CHUU*UUCK.jpg');
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
		$file = $this->createFile();
		$oldName = $file->getFileName();

		$externalPath = $this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$externalPathResult = file_exists($externalPath);

		$catched = false;

		if ($externalPathResult) {
			try {
				$this->fileStorage->renameFile($file, 'CHUUUCK.png');
			} catch (Exception\UploadFilterException $exc) {
				$catched = true;
			}
		} else {
			$this->fail('File should exist in file storage');
		}

		if (($file->getFileName() == $oldName) && $catched) {
			return;
		} else {
			$this->fail('File extension should be the same and rename should throw Exception\UploadFilterException');
		}
	}

	/**
	 * @return \Supra\FileStorage\Entity\File
	 */
	private function createFile($nameSuffix = '')
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

	public function testCreateMultiLevelFolder()
	{
		$this->cleanUp(true);

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$dir = null;
		$dirNames = array('one', 'two', 'three');

		foreach ($dirNames as $dirName) {
			$parentDir = $dir;

			$dir = new \Supra\FileStorage\Entity\Folder();
			$dir->setFileName($dirName);

			$em->persist($dir);
			$em->flush();

			if ($parentDir instanceof \Supra\FileStorage\Entity\Folder) {
				$parentDir->addChild($dir);
			}

			$this->fileStorage->createFolder($dir);
		}

		$internalPath = is_dir($this->fileStorage->getInternalPath() . 'one/two/three');
		$externalPath = is_dir($this->fileStorage->getExternalPath() . 'one/two/three');

		if ($internalPath && $externalPath) {
			$this->assertTrue(true);
		} else {
			$this->fail('There is no folders');
		}
	}

	public function testCreateMultiLevelFolderAndUploadFile()
	{
		$this->cleanUp(true);

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$dir = null;
		$dirNames = array('one', 'two', 'three');
		foreach ($dirNames as $dirName) {
			$parentDir = $dir;
			$dir = new \Supra\FileStorage\Entity\Folder();
			$dir->setFileName($dirName);

			$em->persist($dir);
			$em->flush();

			if ($parentDir instanceof \Supra\FileStorage\Entity\Folder) {
				$parentDir->addChild($dir);
			}

			$this->fileStorage->createFolder($dir);
		}

		$internalPath = is_dir($this->fileStorage->getInternalPath() . 'one/two/three');
		$externalPath = is_dir($this->fileStorage->getExternalPath() . 'one/two/three');

		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';
		$em->flush();
		$file = new \Supra\FileStorage\Entity\File();
		$em->persist($file);

		$fileName = baseName($uploadFile);
		$fileSize = fileSize($uploadFile);
		$file->setFileName($fileName);
		$file->setSize($fileSize);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadFile);
		finfo_close($finfo);
		$file->setMimeType($mimeType);

		$dir->addChild($file);

		try {
			$this->fileStorage->storeFileData($file, $uploadFile);
		} catch (\Exception $exc) {
			$this->fail($exc->getMessage());
			return;
		}

		$em->flush();

		if ($internalPath && $externalPath) {
			$path = $file->getPath(DIRECTORY_SEPARATOR, true);
			$filePath = 'one/two/three/chuck.jpg';
			$fileFullPath = $this->fileStorage->getExternalPath() . $filePath;
			if ( ! ($path == $filePath) || ! file_exists($fileFullPath)) {
				$this->fail('File path is wrong');
			}
		} else {
			$this->fail('There is no folders');
		}
	}

	public function testRemoveDirectoryWithFile()
	{
		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
		$folder = $repo->findOneByFileName('three');

		if (empty($folder)) {
			$this->fail('Folder should exist from previous "testCreateMultiLevelFolderAndUploadFile" test');
		}

		try {
			$this->fileStorage->remove($folder);
		} catch (FileStorage\Exception\RuntimeException $exc) {
			return;
		}

		$em->flush();
	}

	public function testRemoveFile()
	{
		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
		$file = $repo->findOneByFileName('chuck.jpg');

		if (empty($file)) {
			$this->fail('File should exist from previous "testCreateMultiLevelFolderAndUploadFile" test');
		}

		$filePath = $this->fileStorage->getFilesystemPath($file, true);

		$this->fileStorage->remove($file);

		$em->flush();

		self::assertFileNotExists($filePath, 'File should not exist in storage');
	}

	public function testRemoveDirectory()
	{
		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
		$folder = $repo->findOneByFileName('three');

		if (empty($folder)) {
			$this->fail('Folder should exist from previous "testCreateMultiLevelFolderAndUploadFile" test');
		}

		$folderPath = $folder->getPath(DIRECTORY_SEPARATOR, true);

		$folderExternalPath = $this->fileStorage->getExternalPath() . $folderPath;
		$folderInternalPath = $this->fileStorage->getInternalPath() . $folderPath;

		$this->fileStorage->remove($folder);

		if ((is_dir($folderInternalPath)) && (is_dir($folderExternalPath))) {
			$this->fail('Folders should not exist in system');
		}

		$em->flush();
	}

	public function testCreateMultiLevelFolderWithFilesAndSetPrivate()
	{
		$this->cleanUp(true);

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$dir = null;
		$dirNames = array('one', 'two', 'three');
		foreach ($dirNames as $dirName) {
			$parentDir = $dir;
			$dir = new \Supra\FileStorage\Entity\Folder();
			$dir->setFileName($dirName);

			$em->persist($dir);
			$em->flush();

			if ($parentDir instanceof \Supra\FileStorage\Entity\Folder) {
				$parentDir->addChild($dir);
			}

			$this->fileStorage->createFolder($dir);

			$em->flush();
			$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';
			$file = new \Supra\FileStorage\Entity\File();
			$em->persist($file);

			$fileName = baseName($uploadFile);
			$fileSize = fileSize($uploadFile);
			$file->setFileName($fileName);
			$file->setSize($fileSize);
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $uploadFile);
			finfo_close($finfo);
			$file->setMimeType($mimeType);

			$dir->addChild($file);

			try {
				$this->fileStorage->storeFileData($file, $uploadFile);
			} catch (\Exception $exc) {
				$this->fail($exc->getMessage());
				return;
			}

			$em->flush();
		}
		$em->flush();
		$repo = $em->getRepository('Supra\FileStorage\Entity\Folder');
		$node = $repo->findOneByFileName('one');

		$this->fileStorage->setPrivate($node);
		$em->flush();
		$filePath = $this->fileStorage->getInternalPath() . 'one/two/three/chuck.jpg';

		self::assertFileExists($filePath, 'File should exist in internal storage');
	}

	public function testCreateFileAndMoveItToPrivate()
	{
		$this->cleanUp();

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$file = $this->createFile();

		$this->fileStorage->setPrivate($file);
		$em->flush();

		$fileInternalPath = $this->fileStorage->getInternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);
		$fileExternalPath = $this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		self::assertFileExists($fileInternalPath, 'Created file doesn\'t exist in file storage');

		self::assertFileNotExists($fileExternalPath, 'Created file DOES exist in external path after making it private');
	}

	public function testCreateFileAndMoveItToPrivateThenBackToPublic()
	{
		$this->cleanUp();

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$file = $this->createFile();

		$this->fileStorage->setPrivate($file);
		$em->flush();

		$this->fileStorage->setPublic($file);
		$em->flush();

		$filePath = $this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		self::assertFileExists($filePath, 'Created file doesn\'t exist in file storage');
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
		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$query = $em->createQuery("delete from Supra\FileStorage\Entity\ImageSize");
		$query->execute();
		$query = $em->createQuery("delete from Supra\FileStorage\Entity\FilePath");
		$query->execute();	
		$query = $em->createQuery("delete from Supra\FileStorage\Entity\Abstraction\File");
		$query->execute();

		if (self::DELETE_FILES || $delete) {
			$this->deleteFilesAndFolders();
		}

		// Detach all entities
		$em->clear();
	}

	public function testReplaceFile()
	{

		$this->cleanUp(true);

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		// directories
		$dir = $this->createFolder('one');

		$em->flush();

		// file
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';

		$file = new \Supra\FileStorage\Entity\Image();
		$em->persist($file);

		$fileName = baseName($uploadFile);
		$fileSize = fileSize($uploadFile);
		$file->setFileName($fileName);
		$file->setSize($fileSize);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $uploadFile);
		finfo_close($finfo);
		$file->setMimeType($mimeType);
		
		//FIXME: New file/image entity creation must be in some FileStorage method
		$file->setWidth(1);
		$file->setHeight(1);

		$dir->addChild($file);

		$this->fileStorage->storeFileData($file, $uploadFile);

		$em->flush();

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

		$this->fileStorage->replaceFile($file, $replaceFileInfo);

		$replacedFilePath = $this->fileStorage->getExternalPath() . $file->getPath(DIRECTORY_SEPARATOR, true);

		$em->flush();

		self::assertFileExists($replacedFilePath, 'JohnMclane.jpg should replace chuck.jpg. But it\'s obvious that nobody cant replace Chuck Norris');
	}

	public function testFileRepository()
	{
		$this->cleanUp(true);

		$em = ObjectRepository::getEntityManager($this->fileStorage);

		$dir1 = $this->createFolder('one');
		$file1 = $this->createFile();
		$dir1->addChild($file1);

		$dir2 = $this->createFolder('two');
		$file2 = $this->createFile();
		$dir2->addChild($file2);

		$dir = $this->createFolder('three');

		$em->flush();

		// Must be two files
		$fileRepo = $em->getRepository('Supra\FileStorage\Entity\File');
		$files = $fileRepo->findAll();
		self::assertEquals(2, count($files));

		// One parent folder
		$file = $files[0];
		$folders = $file->getAncestors();

		self::assertEquals(1, count($folders));

		// 3 folders
		$folderRepo = $em->getRepository('Supra\FileStorage\Entity\Folder');
		$folders = $folderRepo->findAll();
		self::assertEquals(3, count($folders));

		// 5 elements in total
		$allRepo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
		$all = $allRepo->findAll();
		self::assertEquals(5, count($all));
	}
	
	public function testMoveDirectoryToOtherDirectory() {
		$this->testCreateMultiLevelFolderAndUploadFile();
		
		$em = ObjectRepository::getEntityManager($this->fileStorage);
		$fileRepo =$em->getRepository(FileStorage\Entity\Abstraction\File::CN());
		$one = $fileRepo->findOneBy(array('fileName' => 'one'));
		$three = $fileRepo->findOneBy(array('fileName' => 'three'));
		/* @var $one FileStorage\Entity\Folder */
		/* @var $three FileStorage\Entity\Folder */
		
		$entity = $this->fileStorage->move($three, $one);
		
		$path = $this->fileStorage->getFilesystemPath($entity, true);
		$path .= DIRECTORY_SEPARATOR . 'chuck.jpg';
		
		self::assertTrue(file_exists($path), "Can not find file chuck.jpg in \"{$path}\" after move");
	}
	
	public function testMoveDirectoryToRoot() {
		$this->testCreateMultiLevelFolderAndUploadFile();
		
		$em = ObjectRepository::getEntityManager($this->fileStorage);
		$fileRepo =$em->getRepository(FileStorage\Entity\Abstraction\File::CN());
		$three = $fileRepo->findOneBy(array('fileName' => 'three'));
		/* @var $one FileStorage\Entity\Folder */
		/* @var $three FileStorage\Entity\Folder */
		
		$entity = $this->fileStorage->move($three, '');
		
		$path = $this->fileStorage->getFilesystemPath($entity, true);
		$path .= DIRECTORY_SEPARATOR . 'chuck.jpg';
		
		self::assertTrue(file_exists($path), "Can not find file chuck.jpg in \"{$path}\" after move");
	}
	
	public function testFilePathGeneration()
	{
		$this->testCreateMultiLevelFolderAndUploadFile();

		$em = ObjectRepository::getEntityManager($this->fileStorage);
		$fileRepo = $em->getRepository(FileStorage\Entity\Abstraction\File::CN());
		$chuck = $fileRepo->findOneBy(array('fileName' => 'chuck.jpg'));
		
		/* @var  $filePath \Supra\FileStorage\Entity\FilePath  */
		$this->fileStorage->getWebPath($chuck);
		
		self::assertTrue($this->fileStorage->getWebPath($chuck) === '/files/one/two/three/chuck.jpg');
	}

	public function testCleanUp()
	{
		$this->cleanUp();
	}

}
