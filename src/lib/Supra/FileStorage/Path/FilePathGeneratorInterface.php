<?php

namespace Supra\FileStorage\Path;

use Supra\FileStorage\Entity\Abstraction\File;
use Supra\FileStorage\FileStorage;

interface FilePathGeneratorInterface
{
	/**
	 * @param Transformer\PathTransformerInterface $transformer
	 * @param FileStorage $fileStorage
	 */
	public function __construct(
			Transformer\PathTransformerInterface $transformer,
			FileStorage $fileStorage
	);
	
	/**
	 * @param File $file
	 */
	public function generateFilePath(File $file);	
}