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

	/**
	 * @param string $dirName
	 * @return \Supra\FileStorage\Entity\Folder
	 */
	public function testCreateRootDir($dirName)
	{
		$dir = new \Supra\FileStorage\Entity\Folder();
		$dir->setName($dirName);

		self::getConnection()->persist($dir);
		self::getConnection()->flush();
		
		return $dir;
	}

	public function testUploadFileToExternal()
	{
		$uploadFile = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';

		$dir = $this->testCreateRootDir('rootdir');

		$file = new \Supra\FileStorage\Entity\File();
		self::getConnection()->persist($file);
		
		$dir->addChild($file);

		$file->setName(basename($uploadFile));
		$file->setSize(filesize($uploadFile));
		
		$fileData = new \Supra\FileStorage\Entity\MetaData('en');
		$fileData->setMaster($file);
		$fileData->setTitle(basename($uploadFile));

		$filestorage = FileStorage::getInstance();
		$filestorage->storeFile($file, $uploadFile);

		self::getConnection()->flush();
	}

}
