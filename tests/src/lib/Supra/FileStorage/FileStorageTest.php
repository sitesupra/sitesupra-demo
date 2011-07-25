<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage\FileStorage;
use Supra\FileStorage\UploadFilter;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class FileStorageTest extends \PHPUnit_Extensions_OutputTestCase
{
	
	private static function getConnection()
	{
		return \Supra\Database\Doctrine::getInstance()->getEntityManager();
	}

	public function testUploadFileToExternal()
	{	
		// clean up
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\MetaData");
		$query->execute();
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\Abstraction\File");
		$query->execute();

		// directories
		$dir = null;
		$dirNames = array('one', 'two', 'three', 'four');
		foreach ($dirNames as $dirName) {
			$parentDir = $dir;
			$dir = new \Supra\FileStorage\Entity\Folder();
			$dir->setName($dirName);
			self::getConnection()->persist($dir);
			if ($parentDir instanceof \Supra\FileStorage\Entity\Folder) {
				$parentDir->addChild($dir);
			}
//			// meta-data getter test
//			\Log::debug(array(
//					'Folder __toString(): ' => $dir->__toString(),
//					'Folder getTitle(): ' => $dir->getTitle(),
//					'Folder getDescription(): ' => $dir->getDescription()
//				));
		}
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
		
		$filestorage = FileStorage::getInstance();
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
		// clean up
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\MetaData");
		$query->execute();
		$query = self::getConnection()->createQuery("delete from Supra\FileStorage\Entity\Abstraction\File");
		$query->execute();
		
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
		
		$filestorage = FileStorage::getInstance();
		
		$failed = false;
		try {
			$filestorage->storeFileData($file, $uploadFile);
		} catch (UploadFilter\UploadFilterException $e) {
			$failed = true;
		}
		
		$this->assertTrue($failed);
	}
}
