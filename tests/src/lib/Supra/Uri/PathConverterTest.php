<?php

namespace Supra\Tests\Uri;

use Supra\Uri\PathConverter;
use Project\Blocks\Gallery\GalleryBlock;

class PathConverterTest extends \PHPUnit_Framework_TestCase
{

	public function testGetWebPathFromObject()
	{
		$block = new GalleryBlock();

		self::assertEquals('/components/Blocks/Gallery/123.jpg', PathConverter::getWebPath($block, '123.jpg'));
	}

	public function testGetWebPathFromString()
	{
		$webpath = realpath(SUPRA_WEBROOT_PATH) . '/components/Blocks/Gallery/GalleryBlock.php';
		self::assertEquals('/components/Blocks/Gallery/123.jpg', PathConverter::getWebPath($webpath, '123.jpg'));
	}

	public function testGetWebPathFromStringNotFile()
	{
		$webpath = realpath(SUPRA_WEBROOT_PATH) . '/components/Blocks/Gallery';
		self::assertEquals('/components/Blocks/Gallery/123.jpg', PathConverter::getWebPath($webpath, '123.jpg'));
	}

}