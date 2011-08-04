<?php

namespace Supra\FileStorage\ImageProcessor;

require_once dirname(__FILE__) . '/../../../../../../src/lib/Supra/FileStorage/ImageProcessor/ImageResizer.php';

/**
 * Test class for ImageResizer.
 * Generated by PHPUnit on 2011-08-01 at 17:53:31.
 */
class ImageResizerTest extends \PHPUnit_Framework_TestCase 
{

	/**
	 * @var ImageResizer
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
		$this->object = new ImageResizer;
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
	public function testSetCropMode() 
	{
		$this->object->reset();
		$return = $this->object->setCropMode(true);
		$this->assertEquals($return, $this->object);
		$return = $this->object->setCropMode(false);
		$this->assertEquals($return, $this->object);
		$return = $this->object->setCropMode('genbjwnbjke');
		$this->assertEquals($return, $this->object);
	}

	/**
	 * Simple setter test
	 */
	public function testSetTargetWidth() 
	{
		$this->object->reset();
		$return = $this->object->setTargetWidth(100);
		$this->assertEquals($return, $this->object);
		$return = $this->object->setTargetWidth(-100);
		$this->assertEquals($return, $this->object);
		$return = $this->object->setTargetWidth(0);
		$this->assertEquals($return, $this->object);
	}

	/**
	 * Simple setter test
	 */
	public function testSetTargetHeight() 
	{
		$this->object->reset();
		$return = $this->object->setTargetHeight(100);
		$this->assertEquals($return, $this->object);
		$return = $this->object->setTargetHeight(-100);
		$this->assertEquals($return, $this->object);
		$return = $this->object->setTargetHeight(0);
		$this->assertEquals($return, $this->object);
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
				->setTargetHeight(100)
				->setTargetWidth(100);
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
				->setTargetHeight(100)
				->setTargetWidth(100);
		$this->object->process();
	}

	/**
	 * Test process when target size is invalid
	 * 
	 * @expectedException Supra\FileStorage\Exception\ImageProcessorException
	 */
	public function testProcessTargetInvalid() 
	{
		$this->object->reset();
		$this->object->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setTargetWidth(-100)
				->setTargetHeight(0);
		$this->object->process();
	}
	
	/**
	 * Test process with valid parameters
	 */
	public function testProcess() 
	{

		/* cases */
		
		$sizeCases = array(
			array(
				'w' => 100,
				'h' => 1000
			),
			array(
				'w' => 100,
				'h' => 100
			),
			array(
				'w' => 50,
				'h' => 100
			),
			array(
				'w' => 1,
				'h' => 1
			),
			array(
				'w' => 1000,
				'h' => 1000
			),
		);
		
		/* fit in */ 
		
		foreach ($sizeCases as $case) {
			$this->object->reset();
			$this->object->setSourceFile($this->imagePath)
					->setOutputFile($this->outputPath)
					->setTargetWidth($case['w'])
					->setTargetHeight($case['h']);
			$this->object->process();
			$this->assertFileExists($this->outputPath);
			$size = getimagesize($this->outputPath);
			if ($this->imageWidth > $this->imageHeight) {
				if ($case['w'] <= $this->imageWidth) {
					$this->assertEquals($case['w'], $size[0]);
				} else {
					$this->AssertLessThan($case['w'], $size[0]);
				}
			} else {
				if ($case['h'] <= $this->imageHeight) {
					$this->assertEquals($case['h'], $size[1]);
				} else {
					$this->AssertLessThan($case['h'], $size[1]);
				}
			}
			unlink($this->outputPath);
		}

		/* crop center */ 
		
		foreach ($sizeCases as $case) {
			$this->object->reset();
			$this->object->setSourceFile($this->imagePath)
					->setOutputFile($this->outputPath)
					->setCropMode(true)
					->setTargetWidth($case['w'])
					->setTargetHeight($case['h']);
			$this->object->process();
			$this->assertFileExists($this->outputPath);
			$size = getimagesize($this->outputPath);
			if (($case['w'] <= $this->imageWidth) && ($case['h'] <= $this->imageHeight)) {
				$this->assertEquals($case['w'], $size[0]);
				$this->assertEquals($case['h'], $size[1]);
			} else {
				$this->AssertLessThanOrEqual($case['w'], $size[0]);
				$this->AssertLessThanOrEqual($case['h'], $size[1]);
			}
			unlink($this->outputPath);
		}
		
	}

	/**
	 * Test resize
	 */
	public function testResize() 
	{
		// Method resize() is alternate id for process()
		$this->markTestSkipped('See testProcess()');
	}

	/**
	 * Test reset
	 */
	public function testReset() 
	{
		$this->assertNull($this->object->reset());
	}
	
	/* common method getInfo() of image processor */

	public function testGetImageInfo() 
	{
		$info = $this->object->getImageInfo($this->imagePath);
		$this->assertType('array', $info);
		$this->assertArrayHasKey('mime', $info);
		$this->assertArrayHasKey('width', $info);
		$this->assertArrayHasKey('height', $info);
	}

	/**
	 * @expectedException         Supra\FileStorage\Exception\ImageProcessorException
	 * @expectedExceptionMessage  not found
	 */
	public function testGetImageInfoNotFound() 
	{
		$info = $this->object->getImageInfo('this-file-does-not-exist.lol');
	}

	/**
	 * @expectedException         Supra\FileStorage\Exception\ImageProcessorException
	 * @expectedExceptionMessage  size information
	 */
	public function testGetImageInfoNotImage() 
	{
		$info = $this->object->getImageInfo(__FILE__);
	}
	
}

?>
