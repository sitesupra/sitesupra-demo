<?php

namespace Supra\FileStorage\ImageProcessor;

/**
 * ImageResizer
 *
 */
class ImageResizer extends ImageProcessor
{

	public function process()
	{

		// parameter check
		if (empty($this->sourceFilename)) {
			throw new Exception('Source image is not set');
		}
		if ( ! file_exists($this->sourceFilename)) {
			throw new Exception('Source image does not exist');
		}
		if (empty($this->targetWidth)) {
			throw new Exception('Target width is not set');
		}
		if (empty($this->targetHeight)) {
			throw new Exception('Target height is not set');
		}
		
		$imageInfo = $this->getImageInfo($this->sourceFilename);

		$needsResize = false;
		if (($imageInfo['width'] > $this->targetWidth)
			|| ($imageInfo['height'] > $this->targetHeight)
		) {
			$needsResize = true;
		}
		
		if ($needsResize) {
			$sourceImage = $this->createImageFromFile($this->sourceFilename);
			$resizedImage = 
					imagecreatetruecolor($this->targetWidth, $this->targetHeight);

			if (($imageInfo['mime'] == 'image/png') 
				|| ($imageInfo['mime'] == 'image/png')
			) {
				$this->preserveTransparency($sourceImage, $resizedImage);
			}

			$wRatio = $imageInfo['width'] / $this->targetWidth;
			$hRatio = $imageInfo['height'] / $this->targetHeight;

			$maxRatio = max($wRatio, $hRatio);

			$destWidth = round($imageInfo['width'] / $maxRatio);
			$destHeight = round($imageInfo['height'] / $maxRatio);

			imagecopyresampled($resizedImage, $sourceImage, 
					0, 0, 
					0, 0,
					$destWidth, $destHeight,
					$imageInfo['width'], $imageInfo['height']);

			$this->saveImageToFile($resizedImage, $this->targetFilename, $imageInfo['mime'], $this->targetQuality);

		} else {
			
			copy($this->sourceFilename, $this->targetFilename);
			
		}

	}
}
