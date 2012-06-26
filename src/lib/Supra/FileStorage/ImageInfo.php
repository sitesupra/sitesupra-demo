<?php

namespace Supra\FileStorage;

/**
 * ImageInfo data class
 */
class ImageInfo
{
	public function __construct($filename)
	{
		$info = getimagesize($filename);
		
		//TODO: set data in $this
	}
}
