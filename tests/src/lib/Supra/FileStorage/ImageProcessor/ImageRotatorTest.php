<?php

namespace Supra\FileStorage\ImageProcessor;

require_once dirname(__FILE__) . '/../../../../../../src/lib/Supra/FileStorage/ImageProcessor/ImageRotator.php';

/**
 * Test class for ImageRotator.
 * Generated by PHPUnit on 2011-08-01 at 17:01:59.
 */
class ImageRotatorTest extends \PHPUnit_Framework_TestCase 
{

	/**
	 * @var ImageRotator
	 */
	protected $object;
	
	/**
	 * @var string
	 */
	protected $imagePath;

	/**
	 * @var int
	 */
	protected $imageWidth;

	/**
	 * @var int
	 */
	protected $imageHeight;

	/**
	 *
	 * @var string
	 */
	protected $outputPath;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() 
	{
		$this->object = new ImageRotator;
		$this->imagePath = __DIR__ . '/../chuck.jpg';
		$imageInfo = getimagesize($this->imagePath);
		$this->imageWidth = $imageInfo[0];
		$this->imageHeight = $imageInfo[1];
		$this->outputPath = __DIR__ . '/out.' . pathinfo($this->imagePath, PATHINFO_EXTENSION);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() 
	{
		unlink($this->outputPath);
	}

	/**
	 * Simple setter test
	 */
	public function testSetRotationCount() 
	{
		$this->object->reset();
		$return = $this->object->setRotationCount(-1337);
		$this->assertEquals($return, $this->object);
	}

	/**
	 * Test process
	 */
	public function testProcess() 
	{
		$this->object->reset();
		$this->object->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setRotationCount(-6);
		$this->object->process();
		$this->assertFileExists($this->outputPath);
		$size = getimagesize($this->outputPath);
		$this->assertEquals($this->imageWidth, $size[0]);
		$this->assertEquals($this->imageHeight, $size[1]);
		unlink($this->outputPath);

		$this->object->reset();
		$this->object->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setRotationCount(3);
		$this->object->process();
		$this->assertFileExists($this->outputPath);
		$size = getimagesize($this->outputPath);
		$this->assertEquals($this->imageHeight, $size[0]);
		$this->assertEquals($this->imageWidth, $size[1]);
		unlink($this->outputPath);
	}

	/**
	 * Test process when source file not found
	 * 
	 * @expectedException         Supra\FileStorage\Exception\ImageProcessorException
	 * @expectedExceptionMessage  Source image
	 */
	public function testProcessSourceNotFound() 
	{
		$this->object->reset();
		$this->object
				->setSourceFile('this-file-does-not-exist.lol')
				->setOutputFile('output-here.out')
				->setRotationCount(1);
		$this->object->process();
	}

	/**
	 * Test process when output file is not set
	 * 
	 * @expectedException         Supra\FileStorage\Exception\ImageProcessorException
	 * @expectedExceptionMessage  Target
	 */
	public function testProcessOutputNotSet() 
	{
		$this->object->reset();
		$this->object
				->setSourceFile($this->imagePath)
				->setRotationCount(1);
		$this->object->rotate();
	}

	/**
	 * Test rotate right
	 */
	public function testRotateRight() 
	{
		$this->object->reset();
		$this->object->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath);
		$this->object->rotateRight();
		$this->assertFileExists($this->outputPath);
		$size = getimagesize($this->outputPath);
		$this->assertEquals($this->imageHeight, $size[0]);
		$this->assertEquals($this->imageWidth, $size[1]);
		unlink($this->outputPath);
	}

	/**
	 * Test rotate left
	 */
	public function testRotateLeft() 
	{
		$this->object->reset();
		$this->object->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath);
		$this->object->rotateLeft();
		$this->assertFileExists($this->outputPath);
		$size = getimagesize($this->outputPath);
		$this->assertEquals($this->imageHeight, $size[0]);
		$this->assertEquals($this->imageWidth, $size[1]);
		unlink($this->outputPath);
	}

	/**
	 * Test rotate 180
	 */
	public function testRotate180() 
	{
		$this->object->reset();
		$this->object->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath);
		$this->object->rotate180();
		$this->assertFileExists($this->outputPath);
		$size = getimagesize($this->outputPath);
		$this->assertEquals($this->imageWidth, $size[0]);
		$this->assertEquals($this->imageHeight, $size[1]);
		unlink($this->outputPath);
	}

	/**
	 * Test reset
	 */
	public function testReset() 
	{
		$this->assertNull($this->object->reset());
	}

}

?>
