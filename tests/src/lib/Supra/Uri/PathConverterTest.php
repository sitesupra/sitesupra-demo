<?php

namespace Supra\Tests\Uri;

use Supra\Uri\PathConverter;
use Project\Blocks\Gallery\GalleryBlock;

class PathConverterTest extends \PHPUnit_Framework_TestCase
{

	public function testGetWebPathFromObject()
	{
		$block = new GalleryBlock();
		self::assertEquals('/components/Blocks/Gallery/icon.png', PathConverter::getWebPath('icon.png', $block));
	}

	public function testGetWebPathFromString()
	{
		$blockClass = GalleryBlock::CN();
		self::assertEquals('/components/Blocks/Gallery/icon.png', PathConverter::getWebPath('icon.png', $blockClass));
	}

	public function testGetWebPathFromStringNotFile()
	{
		$webpath = realpath(SUPRA_WEBROOT_PATH) . '/components/Blocks/Gallery';
		self::assertEquals('/components/Blocks/Gallery/icon.png', PathConverter::getWebPath($webpath . '/icon.png'));
	}
}
