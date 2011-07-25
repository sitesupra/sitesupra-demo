<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage\FileStorage;

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
		
//		$repo = $dir->getRepository();
		$repo = self::getConnection()->getRepository("Supra\FileStorage\Entity\Folder");
		$roots = $repo->getRootNodes();
		1+1;
	}

}
