<?php

namespace Supra\Tests\FileStorage;

use Supra\FileStorage;
use Supra\FileStorage\Exception;
use Supra\ObjectRepository\ObjectRepository;

class ImageInfoTest extends FileStorageTestAbstraction
{

	private $image;

	public function createImage()
	{
		$this->cleanUp(true);

		return parent::createImage();
	}

	public function testGetImageInfoFromFile()
	{
		$image = __DIR__ . DIRECTORY_SEPARATOR . 'chuck.jpg';
		$info = new FileStorage\ImageInfo($image);

		self::assertTrue($info->getName() === pathinfo($image, PATHINFO_BASENAME));
	}

	public function testGetImageInfoFromEntity()
	{
		$image = $this->createImage();
		$info = new FileStorage\ImageInfo($image);

		self::assertTrue($info->getName() === $image->getFileName());
	}

	public function testCleanUp()
	{
		$this->image = null;
		$this->cleanUp(true);
	}

}