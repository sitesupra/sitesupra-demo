<?php

namespace Supra\Tests\FileStorage;

use Supra\Tests\TestCase;
use Supra\FileStorage\ImageProcessor\ImageResizer;
use Supra\FileStorage\ImageProcessor\ImageCropper;
use Supra\FileStorage\ImageProcessor\ImageRotator;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class FileStorageTest extends \PHPUnit_Extensions_OutputTestCase
{
	
	public function testGetImageInfo()
	{	
		$filePath = __DIR__ . DIRECTORY_SEPARATOR  . '..' . DIRECTORY_SEPARATOR . 'chuck.jpg';

//		$resizer = new ImageResizer;
//
//		$imageInfo = $resizer->getImageInfo($filePath);
//
//		$w = $resizer->getImageWidth($filePath);
//		$h = $resizer->getImageHeight($filePath);
//		
//		$resizer->setSourceFile($filePath)
//				->setOutputFile($filePath . '.fit.jpg')
//				->setTargetHeight(100)
//				->setTargetWidth(100);
//		$resizer->process();
//
//		$resizer->setOutputFile($filePath . '.crop.jpg')
//				->setCropMode(true);
//		$resizer->process();

//		$cropper = new ImageCropper;
//		$cropper->setSourceFile($filePath)
//				->setOutputFile($filePath . '.cropped.jpg')
//				->setLeft(100)
//				->setTop(50)
//				->setBottom(-50)
//				->setRight(-100);
//		$cropper->process();

		$rotator = new ImageRotator;
		$rotator->setSourceFile($filePath)
				->setOutputFile($filePath . '.rotated.jpg')
				->setRotationCount(-3);
		$rotator->rotate180();
		
		1+1;
	}
	
}
