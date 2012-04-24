<?php

namespace Supra\FileStorage\Exception;

/**
 * Thrown on upload, if uploaded file is an image, and current available memory
 * amount is lower than approx. calculated one 
 */
class InsufficientSystemResources extends UploadFilterException
{
	
}
