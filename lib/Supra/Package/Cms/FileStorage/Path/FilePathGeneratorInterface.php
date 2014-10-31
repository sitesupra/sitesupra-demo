<?php

namespace Supra\Package\Cms\FileStorage\Path;

use Supra\Package\Cms\Entity\Abstraction\File;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\FileStorage\Path\Transformer\PathTransformerInterface;

interface FilePathGeneratorInterface
{
	/**
	 * @param PathTransformerInterface $transformer
	 * @param FileStorage $fileStorage
	 */
	public function __construct(
			PathTransformerInterface $transformer,
			FileStorage $fileStorage
	);
	
	/**
	 * @param File $file
	 */
	public function generateFilePath(File $file);	
}